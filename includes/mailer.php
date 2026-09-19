<?php
/**
 * Mailer Helper — StudentHub
 * Handles sending password reset emails.
 *
 * Supports:
 * 1. Standard PHP mail()
 * 2. Pure PHP SMTP Socket client (e.g. Gmail SMTP with App Password)
 * 3. Local notification logging for development and viva demonstration
 */

// SMTP Configuration (Optional: set SMTP_ENABLED to true if using a Gmail App Password)
if (!defined('SMTP_ENABLED')) {
    define('SMTP_ENABLED', false); // Set to true to route through live Gmail SMTP
}
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.gmail.com');
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', 587); // 587 for TLS, 465 for SSL
}
if (!defined('SMTP_USER')) {
    define('SMTP_USER', '');
}
if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', ''); // 16-character Google App Password (e.g. 'abcd efgh ijkl mnop')
}

/**
 * Pure PHP SMTP Socket Client
 * Connects directly to SMTP server with TLS without requiring third-party libraries.
 */
function sendViaSmtpSocket($to, $subject, $htmlMessage, $fromName, $fromEmail) {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $username = SMTP_USER;
    $password = SMTP_PASS;

    $socket = @fsockopen($host, $port, $errno, $errstr, 15);
    if (!$socket) {
        return ['success' => false, 'error' => "Cannot connect to $host:$port ($errstr)"];
    }

    $response = fgets($socket, 515);

    // EHLO
    fputs($socket, "EHLO " . gethostname() . "\r\n");
    $response = readSmtpResponse($socket);

    // STARTTLS
    fputs($socket, "STARTTLS\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) !== '220') {
        fclose($socket);
        return ['success' => false, 'error' => 'STARTTLS failed: ' . $response];
    }

    // Enable crypto
    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        fclose($socket);
        return ['success' => false, 'error' => 'TLS crypto handshake failed'];
    }

    // EHLO after TLS
    fputs($socket, "EHLO " . gethostname() . "\r\n");
    $response = readSmtpResponse($socket);

    // AUTH LOGIN
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 515);

    fputs($socket, base64_encode($username) . "\r\n");
    $response = fgets($socket, 515);

    fputs($socket, base64_encode($password) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) !== '235') {
        fclose($socket);
        return ['success' => false, 'error' => 'SMTP Authentication failed. Please check your App Password.'];
    }

    // MAIL FROM
    fputs($socket, "MAIL FROM: <$username>\r\n");
    $response = fgets($socket, 515);

    // RCPT TO
    fputs($socket, "RCPT TO: <$to>\r\n");
    $response = fgets($socket, 515);

    // DATA
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 515);

    // Headers & Payload
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: StudentHub <$username>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Reply-To: " . str_replace(["\r", "\n", '"'], '', $fromName) . " <$fromEmail>\r\n";
    $safeSubject = str_replace(["\r", "\n"], '', $subject);
    $headers .= "Subject: $safeSubject\r\n";
    $headers .= "Date: " . date('r') . "\r\n";

    fputs($socket, $headers . "\r\n" . $htmlMessage . "\r\n.\r\n");
    $response = fgets($socket, 515);

    // QUIT
    fputs($socket, "QUIT\r\n");
    fclose($socket);

    if (substr($response, 0, 3) === '250') {
        return ['success' => true, 'error' => ''];
    }

    return ['success' => false, 'error' => 'Message rejected: ' . $response];
}

/**
 * Helper to read multiline SMTP response
 */
function readSmtpResponse($socket) {
    $data = "";
    while ($str = fgets($socket, 515)) {
        $data .= $str;
        if (substr($str, 3, 1) === " ") {
            break;
        }
    }
    return $data;
}

/**
 * Log mail dispatch for testing and audit
 */
function logMailDispatch($recipient, $senderName, $senderEmail, $subject, $method) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/mail_log.txt';
    $time = date('Y-m-d H:i:s');
    $entry = "[$time] To: $recipient | From: $senderName ($senderEmail) | Subject: $subject | Method: $method\n";
    @file_put_contents($logFile, $entry, FILE_APPEND);
}

/**
 * Send Password Reset Link Email
 *
 * @param string $name User's name
 * @param string $email User's email
 * @param string $resetLink The generated password reset URL
 * @return array ['success' => bool, 'method' => string, 'error' => string]
 */
function sendPasswordResetEmail($name, $email, $resetLink) {
    $mailSubject = "Reset Your StudentHub Password";

    $htmlBody = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Reset Your Password</title>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f5f7fa; margin: 0; padding: 24px; color: #1a1a2e; }
            .email-wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e8ecf1; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
            .email-header { background: #0d1b2a; padding: 24px 28px; text-align: left; border-bottom: 3px solid #00bcd4; }
            .email-header h2 { margin: 0; color: #ffffff; font-size: 20px; font-weight: 700; }
            .email-header span { color: #00bcd4; }
            .email-body { padding: 32px 28px; }
            .badge-notice { display: inline-block; background: rgba(0,188,212,0.12); color: #008fa3; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 18px; }
            .btn-reset { display: inline-block; background: linear-gradient(135deg, #00bcd4, #00a5bb); color: #ffffff !important; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 15px; margin: 20px 0; box-shadow: 0 4px 14px rgba(0,188,212,0.3); }
            .url-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; font-size: 12px; word-break: break-all; color: #6c757d; margin-top: 15px; }
            .email-footer { background: #f8fafc; padding: 18px 28px; font-size: 12px; color: #8e99a4; text-align: center; border-top: 1px solid #edf2f7; }
        </style>
    </head>
    <body>
        <div class='email-wrapper'>
            <div class='email-header'>
                <h2>Student<span>Hub</span> &mdash; Account Recovery</h2>
            </div>
            <div class='email-body'>
                <div class='badge-notice'>Password Reset Request</div>
                <h3 style='margin-top: 0; color: #0d1b2a;'>Hello " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ",</h3>
                <p>We received a request to reset your password for your StudentHub account. Click the button below to choose a new password:</p>

                <div style='text-align: center;'>
                    <a href='" . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . "' class='btn-reset'>
                        Reset My Password
                    </a>
                </div>

                <p style='font-size: 13px; color: #6c757d; margin-top: 20px;'>
                    This password reset link is valid for <strong>1 hour</strong>. If you did not request a password reset, you can safely ignore this email &mdash; your password will remain unchanged.
                </p>

                <div class='url-box'>
                    If the button above does not work, copy and paste this link into your browser:<br>
                    <a href='" . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . "' style='color: #00bcd4;'>" . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . "</a>
                </div>
            </div>
            <div class='email-footer'>
                StudentHub &bull; Faculty of Technology, Rajarata University of Sri Lanka &bull; ICT 2209 Web Technologies
            </div>
        </div>
    </body>
    </html>
    ";

    $result = ['success' => false, 'method' => 'none', 'error' => ''];

    // 1. Try SMTP if enabled
    if (SMTP_ENABLED && !empty(SMTP_PASS)) {
        $smtpResult = sendViaSmtpSocket($email, $mailSubject, $htmlBody, 'StudentHub Security', 'no-reply@studenthub.lk');
        if ($smtpResult['success']) {
            $result['success'] = true;
            $result['method'] = 'SMTP';
        } else {
            $result['error'] = $smtpResult['error'];
        }
    }

    // 2. Try PHP mail()
    if (!$result['success']) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: StudentHub <no-reply@studenthub.lk>\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        if (@mail($email, $mailSubject, $htmlBody, $headers)) {
            $result['success'] = true;
            $result['method'] = 'PHP mail()';
        }
    }

    logMailDispatch($email, 'StudentHub Security', 'no-reply@studenthub.lk', $mailSubject, $result['method']);

    return $result;
}

