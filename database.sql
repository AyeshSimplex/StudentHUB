-- ============================================
-- StudentHub Database
-- Rajarata University of Sri Lanka
-- Faculty of Technology - Department of Materials
-- ICT 2209 Web Technologies Mini Project
-- ============================================

-- Create the database
CREATE DATABASE IF NOT EXISTS `studenthub` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `studenthub`;

-- ============================================
-- Users Table
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `faculty` VARCHAR(100) DEFAULT NULL,
    `skills` TEXT DEFAULT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `reset_token` VARCHAR(64) DEFAULT NULL,
    `reset_token_expires` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Projects Table
-- ============================================
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `technologies` TEXT DEFAULT NULL,
    `skills` TEXT DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `project_url` VARCHAR(255) DEFAULT NULL,
    `github_url` VARCHAR(255) DEFAULT NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Project Images Table (Gallery & Multiple Screenshots)
-- ============================================
CREATE TABLE IF NOT EXISTS `project_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `image` VARCHAR(255) NOT NULL,
    `caption` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Messages Table (Contact Form)
-- ============================================
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `user_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Message Replies Table
-- ============================================
CREATE TABLE IF NOT EXISTS `message_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `message_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `reply_text` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Reviews Table (Project Reviews & Star Ratings)
-- ============================================
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `review_text` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Demo User Account
-- Email: demo@studenthub.lk
-- Password: Demo@123
-- ============================================
INSERT INTO `users` (`username`, `full_name`, `email`, `password`, `faculty`, `skills`) VALUES
('ayesh', 'Ayesh Rathnayaka', 'demo@studenthub.lk', '$2y$10$Z5qUkQc3su9hZwncCJMzCebYq/SG6n4Rka29z1WqaQc5.5dovyMWG', 'Faculty of Technology', 'PHP, MySQL, JavaScript, HTML, CSS, Python, Arduino, IoT'),
('nimasha', 'Nimasha Fernando', 'nimasha@studenthub.lk', '$2y$10$Z5qUkQc3su9hZwncCJMzCebYq/SG6n4Rka29z1WqaQc5.5dovyMWG', 'Faculty of Technology', 'Python, Machine Learning, Data Analysis, IoT, Electronics'),
('tharindu', 'Tharindu Silva', 'tharindu@studenthub.lk', '$2y$10$Z5qUkQc3su9hZwncCJMzCebYq/SG6n4Rka29z1WqaQc5.5dovyMWG', 'Faculty of Applied Sciences', 'Materials Science, Polymer Chemistry, Lab Analysis, Research Writing');

-- ============================================
-- Sample Projects
-- ============================================

-- Project 1: Smart Waste Management System
INSERT INTO `projects` (`title`, `description`, `category`, `technologies`, `skills`, `image`, `project_url`, `github_url`, `user_id`) VALUES
('Smart Waste Management System',
'A comprehensive IoT-based waste management solution designed for the university campus. The system uses ultrasonic sensors placed inside waste bins to monitor fill levels in real time. Data is transmitted via Wi-Fi modules to a central dashboard, allowing maintenance staff to optimize collection routes and schedules. The platform includes automated alerts when bins reach capacity, historical analytics for waste generation patterns, and a mobile-friendly interface for field workers. This project aims to reduce operational costs and promote sustainable waste handling practices across campus facilities.',
'Engineering',
'Arduino, ESP8266, PHP, MySQL, Bootstrap, Chart.js',
'IoT Development, Circuit Design, Backend Development, Data Visualization, Problem Solving',
NULL, NULL, 'https://github.com/demo/smart-waste', 1);

-- Project 2: Low-Cost Water Quality Monitoring System
INSERT INTO `projects` (`title`, `description`, `category`, `technologies`, `skills`, `image`, `project_url`, `github_url`, `user_id`) VALUES
('Low-Cost Water Quality Monitoring System',
'An affordable and portable water quality monitoring device built using Arduino microcontrollers and electrochemical sensors. The system measures key parameters including pH level, turbidity, dissolved oxygen, and temperature of water samples. Readings are displayed on an LCD screen and simultaneously logged to a web-based dashboard via a Bluetooth module. The project was developed to assist rural communities in the North Central Province where access to laboratory testing facilities is limited. Field trials demonstrated that the device provides readings within acceptable accuracy ranges compared to standard laboratory equipment, making it a viable tool for preliminary water safety assessments.',
'Science',
'Arduino, Sensors, Python, Flask, SQLite, Bluetooth',
'Embedded Systems, Sensor Calibration, Scientific Research, Data Logging, Technical Writing',
NULL, NULL, 'https://github.com/demo/water-quality', 2);

-- Project 3: Sustainable Polymer Composite Development
INSERT INTO `projects` (`title`, `description`, `category`, `technologies`, `skills`, `image`, `project_url`, `github_url`, `user_id`) VALUES
('Sustainable Polymer Composite Development',
'A materials technology research project focused on developing biodegradable polymer composites reinforced with natural fibers sourced from coconut coir and banana stem. The study investigates the mechanical properties, thermal stability, and biodegradation rates of various composite formulations under controlled laboratory conditions. Tensile strength tests, thermogravimetric analysis, and soil burial degradation experiments were conducted over a six-month period. Results indicate that a 30 percent natural fiber content yields optimal strength-to-weight ratios while maintaining acceptable biodegradation timelines. The findings contribute to ongoing efforts to reduce reliance on synthetic plastics in packaging and construction applications.',
'Materials Technology',
'MATLAB, Lab Equipment, Statistical Analysis, LaTeX',
'Materials Testing, Research Methodology, Data Analysis, Scientific Writing, Laboratory Skills',
NULL, NULL, NULL, 3);

-- Project 4: Student Attendance Management System
INSERT INTO `projects` (`title`, `description`, `category`, `technologies`, `skills`, `image`, `project_url`, `github_url`, `user_id`) VALUES
('Student Attendance Management System',
'A web-based attendance tracking platform designed to replace the traditional paper-based sign-in sheets used across university departments. Lecturers can create course sessions, generate unique QR codes for each class, and students mark attendance by scanning the code through their mobile devices. The system includes geolocation verification to ensure students are physically present in the lecture hall. An administrative dashboard provides detailed attendance reports with visual charts, exportable CSV records, and automated email notifications for students falling below the minimum attendance threshold. The platform supports multiple user roles including administrators, lecturers, and students with appropriate permission levels.',
'Information Technology',
'PHP, MySQL, JavaScript, Bootstrap, QR Code API, Chart.js',
'Full Stack Development, Database Design, API Integration, UI/UX Design, Project Management',
NULL, 'http://localhost/attendance', 'https://github.com/demo/attendance-system', 1);

-- Project 5: Smart Agriculture Monitoring System
INSERT INTO `projects` (`title`, `description`, `category`, `technologies`, `skills`, `image`, `project_url`, `github_url`, `user_id`) VALUES
('Smart Agriculture Monitoring System',
'An integrated agricultural monitoring platform that helps small-scale farmers in the dry zone optimize irrigation and crop management decisions. The system deploys soil moisture sensors, temperature and humidity sensors, and light intensity sensors across farmland. Data is collected by a Raspberry Pi gateway and uploaded to a cloud-based dashboard where farmers can view real-time conditions and historical trends. The platform includes a rule-based recommendation engine that suggests optimal watering schedules based on crop type, current soil conditions, and weather forecast data obtained through a public API. SMS alerts are sent to farmers when sensor readings fall outside configured thresholds, enabling timely intervention even without internet access.',
'Agriculture',
'Raspberry Pi, Python, Firebase, HTML, CSS, JavaScript, Twilio API',
'IoT Architecture, Cloud Computing, API Integration, Agricultural Science, User Research',
NULL, NULL, 'https://github.com/demo/smart-agriculture', 2);

-- Project 6: University Event Management Platform
INSERT INTO `projects` (`title`, `description`, `category`, `technologies`, `skills`, `image`, `project_url`, `github_url`, `user_id`) VALUES
('University Event Management Platform',
'A centralized event management web application built to streamline how student societies and departments organize campus events at Rajarata University. The platform allows event organizers to create event listings with descriptions, schedules, venue maps, and speaker profiles. Students can browse upcoming events, register their attendance, and receive email confirmations with calendar invitations. The system features an approval workflow where the Student Affairs office reviews and approves event submissions before they are published. A real-time analytics dashboard tracks registration numbers, attendance rates, and participant feedback collected through post-event surveys. The platform has successfully managed over fifty campus events during its pilot semester.',
'Information Technology',
'PHP, MySQL, JavaScript, Bootstrap, PHPMailer, FullCalendar.js',
'Web Development, Database Management, Email Integration, Event Planning, User Interface Design',
NULL, 'http://localhost/events', 'https://github.com/demo/event-platform', 1);
