# StudentHub 🎓

**Showcase Your Skills. Share Your Projects. Build Your Future.**

StudentHub is a comprehensive, full-stack web application designed for university students to publish portfolios, showcase academic and engineering projects, collaborate with peers, and share technical skills.

> 🌐 **Live Application Demo:** [https://ayesh-studenthub.page.gd](https://ayesh-studenthub.page.gd)  
> **Status:** Active & Deployed Online

---

> **Institution:** Rajarata University of Sri Lanka  
> **Faculty:** Faculty of Technology — Department of Materials Technology  
> **Course Module:** ICT 2209 Web Technologies Mini Project  
> **Academic Year:** 2026  

---

## 📑 Table of Contents
1. [Key Features](#-key-features)
2. [Technology Stack](#-technology-stack)
3. [System Requirements](#-system-requirements)
4. [Installation & Setup (XAMPP / WAMP)](#-installation--setup-guide)
   - [Option A: Running on WAMP Server](#option-a-running-on-wamp-server)
   - [Option B: Running on XAMPP](#option-b-running-on-xampp)
5. [Database Import Guide](#-database-import-guide)
6. [Demo User Credentials](#-demo-user-credentials)
7. [Project Structure](#-project-structure)
8. [Security & Validation Implementations](#-security--validation-implementations)
9. [Submission & License](#-submission--license)

---

## 🚀 Key Features

### 👤 User Authentication & Profile Management
- **Student Registration & Login:** Secure authentication powered by `password_hash()` (bcrypt) and session management.
- **Profile Photo Upload:** Custom avatar uploading with instant image preview during registration and in account settings.
- **Dynamic Avatars:** Displays custom uploaded photos or elegant initial fallbacks across navigation, dashboard, profiles, project cards, and listings.
- **Account Settings:** Tabbed settings interface to change email address, update password with visibility toggles, and edit faculty or skills details.
- **Password Recovery Workflow:** Self-service "Forgot Password" workflow generating 1-hour secure cryptographic reset tokens with local audit logging.
- **Danger Zone (Permanent Account Deletion):** Two-step verification modal with password confirmation and typing safeguards to permanently delete account and cascade-remove all projects and uploaded media from disk and database.

### 💼 Project Showcase & Multi-Image Gallery
- **Project Publishing & CRUD:** Students can publish, view, edit, and delete academic and technical projects with categories, skill tags, live URLs, and GitHub links.
- **Multi-Image Photo Gallery:** Upload primary project cover images and multiple project screenshots during creation or post-publishing.
- **Interactive Lightbox & Management:** Full-screen modal lightbox for browsing screenshots, with one-click cover image reassignment and safe image deletion.
- **Author Control Shortcuts:** Direct contextual action buttons for authors to edit or manage projects directly from project details, listings, or profiles.

### 🔍 Discovery & Interaction
- **Hero Carousel:** Responsive interactive carousel featuring university project highlights with touch/arrow controls and glassmorphic styling.
- **Live Search & Category Filters:** Real-time client-side project search by title, technologies, or student name, combined with category filters and sorting options without page reloads.
- **Public Profiles:** Public student portfolios highlighting biographical data, faculty, skills badges, and all published projects.
- **Reviews & Star Ratings (NEW):** Interactive 5-star rating and review system on project pages with automatic average calculation and user-managed reviews.
- **Project Sharing (NEW):** Native Web Share API integration for seamless mobile/desktop sharing, with a clipboard fallback. Includes Open Graph (OG) and Twitter Card meta tags for rich link previews (image, title, description) when sharing URLs on social media platforms like WhatsApp, Facebook, and Discord.
- **PWA & SEO Optimized (NEW):** Includes a Web Manifest (`site.webmanifest`), Apple Touch icons, and modern Favicons, allowing the platform to be installed as a Progressive Web App (PWA) on mobile devices with a native app-like experience.
- **Responsive UI Enhancements:** Optimized action buttons (View, Edit, Share, GitHub) with intelligent flexbox layouts for perfect alignment and wrapping behavior across mobile and desktop screens.
- **Clickable Project Cards:** Enhanced UX allowing users to click anywhere on a project gig card to view details, while keeping sub-actions (like 'Edit') functional.
- **Contact Form & Public Notice:** Inquiries submitted via the contact form are stored in the database. Features a public visibility warning and no longer dispatches internal emails for privacy.
- **Dashboard Message Management (NEW):** Students and Admins can view, edit, reply, or delete their own messages and replies directly from the dashboard. Admin replies appear as 'StudentHub Team'.
- **User Dashboard Metrics (NEW):** The inquiries counter accurately reflects only the current user's submitted messages for better privacy.

### 👑 Admin Capabilities (Moderation)
- **Privacy Access:** While the "Public Messages" section is visible to all logged-in users, only Administrators can see the private email addresses of the users who submitted inquiries.
- **Message Moderation:** Privileges to delete any public message or reply submitted by any user across the platform.
- **Review Moderation:** Ability to remove any project review or rating to maintain community standards, even if they are not the author.
- **Official Responses:** Replies submitted by an administrator to any user inquiry are represented officially as 'StudentHub Team'.
- **Admin Assignment:** The admin role is securely managed via an `is_admin` database column (Role-Based Access Control). Registration with reserved administrative emails or usernames is strictly blocked to prevent privilege escalation.

---

## 🛠 Technology Stack

| Layer | Technology | Description |
|---|---|---|
| **Frontend Structure** | HTML5 | Semantic markup and accessibility standards |
| **Styling & Theme** | CSS3 & Vanilla CSS | Modern navy & cyan theme, glassmorphism, responsive grid |
| **UI Framework** | Bootstrap 5.3.2 | Responsive navigation, cards, modals, and grid system |
| **Icons** | Bootstrap Icons 1.11 | Modern iconography across all components |
| **Client-Side Scripting** | JavaScript (ES6+) | Live search, client validation, dropzone drag-and-drop, lightbox |
| **Server-Side Runtime** | PHP 8.0+ (Tested on 8.2) | Procedural & OOP backend handling authentication, forms, and uploads |
| **Database Engine** | MySQL / MariaDB (InnoDB) | Relational database with Foreign Key cascades |
| **Local Server** | WAMP Server / XAMPP | Apache web server and MySQL database environment |

---

## 💻 System Requirements

Before running the application, make sure you have:
- **Operating System:** Windows 10/11, macOS, or Linux
- **Local Server Stack:** [WAMP Server](https://www.wampserver.com/) (recommended on Windows) OR [XAMPP](https://www.apachefriends.org/)
- **PHP Version:** PHP 8.0 or higher (PHP 8.2 recommended)
- **MySQL Version:** MySQL 5.7+ or MariaDB 10.4+
- **Web Browser:** Google Chrome, Mozilla Firefox, Microsoft Edge, or Safari

---

## ⚙ Installation & Setup Guide

### Option A: Running on WAMP Server

1. **Start WAMP Server:**
   - Launch WAMP Server from the Start Menu.
   - Wait for the WAMP notification area icon in the taskbar to turn **GREEN** (indicating Apache and MySQL are running).

2. **Place Project Files:**
   - Copy or clone the `StudentHUB` directory into your WAMP `www` folder:
     ```text
     C:\wamp64\www\StudentHUB
     ```

3. **Import the Database:**
   - Follow the [Database Import Guide](#-database-import-guide) below.

4. **Verify Database Configuration:**
   - Open `C:\wamp64\www\StudentHUB\includes\db.php` in any code editor.
   - Default WAMP settings (no password) are already pre-configured:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');           // Default WAMP root password is empty
     define('DB_NAME', 'studenthub');
     ```

5. **Open Application in Browser:**
   - Visit: [http://localhost/StudentHUB/](http://localhost/StudentHUB/)

---

### Option B: Running on XAMPP

1. **Start XAMPP Server:**
   - Open the **XAMPP Control Panel**.
   - Click **Start** next to **Apache**.
   - Click **Start** next to **MySQL**.
   - Ensure both modules show green highlights and running status.

2. **Place Project Files:**
   - Copy or clone the `StudentHUB` directory into your XAMPP `htdocs` folder:
     ```text
     C:\xampp\htdocs\StudentHUB
     ```

3. **Import the Database:**
   - Follow the [Database Import Guide](#-database-import-guide) below.

4. **Verify Database Configuration:**
   - Open `C:\xampp\htdocs\StudentHUB\includes\db.php` in a text editor.
   - Default XAMPP settings:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');           // Default XAMPP root password is empty
     define('DB_NAME', 'studenthub');
     ```
   *(If you configured a root password in your XAMPP MySQL, enter it in `DB_PASS`)*.

5. **Open Application in Browser:**
   - Visit: [http://localhost/StudentHUB/](http://localhost/StudentHUB/)

---

## 🗄 Database Import Guide

The project includes a complete export file [`database.sql`](database.sql) containing the database structure and initial demo data.

### Step-by-Step phpMyAdmin Import:

1. Open your web browser and navigate to:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Log in (default username is `root`, password is empty for both WAMP and XAMPP unless previously changed).
3. In the left navigation panel, click **New** to create a database.
4. Set the **Database name** to:
   ```text
   studenthub
   ```
5. Select Collation: `utf8mb4_general_ci` and click **Create**.
6. Select the newly created `studenthub` database from the left sidebar.
7. Click the **Import** tab on the top menu bar.
8. Under **File to import**, click **Choose File** (or *Browse...*) and select:
   ```text
   database.sql
   ```
   *(Located in the root of the project folder)*.
9. Scroll to the bottom and click the **Import** (or **Go**) button.
10. You should see a success message: *"Import has been successfully finished, queries executed."*

---

## 🔑 Demo User Credentials

The database includes ready-to-test student accounts:

| Role / Student | Email Address | Password | Faculty |
|---|---|---|---|
| **Primary Demo** | `demo@studenthub.lk` | `Demo@123` | Faculty of Technology |
| **Sample Student 2** | `nimasha@studenthub.lk` | `Demo@123` | Faculty of Technology |
| **Sample Student 3** | `tharindu@studenthub.lk` | `Demo@123` | Faculty of Applied Sciences |

> 💡 **Tip:** You can also register a brand new account anytime via [register.php](http://localhost/StudentHUB/register.php) with your own custom profile photo.

---

## 📂 Project Structure

```text
StudentHUB/
│
├── .gitignore                   # Git ignore patterns for OS, IDE, and media
├── database.sql                 # Complete MySQL schema & initial seed data
├── README.md                    # Project documentation and submission guide
│
├── index.php                    # Landing page with hero carousel & featured projects
├── projects.php                 # Searchable & filterable projects catalog
├── project-details.php          # Detailed project view with multi-image gallery & lightbox
├── about.php                    # Department & university about page
├── contact.php                  # Contact and inquiry submission form
│
├── login.php                    # Student authentication
├── register.php                 # Registration with avatar upload
├── logout.php                   # Session termination
├── forgot-password.php          # Password reset request generator
├── reset-password.php           # Secure token-based password reset
├── dashboard.php                # Student dashboard & message inbox
├── profile.php                  # Public student portfolio page
├── settings.php                 # Account settings & Danger Zone account deletion
│
├── add-project.php              # Publish project with multi-image upload
├── edit-project.php             # Edit project & manage gallery photos
├── delete-project.php           # Delete project and unlink images from disk
├── upload-project-images.php    # Post-publish screenshot gallery uploader
├── set-cover-image.php          # Reassign primary cover image
├── delete-project-image.php     # Delete individual screenshot
├── submit-review.php            # Handle 5-star ratings and reviews submission
├── delete-review.php            # Allow users to delete their own reviews
├── edit-message.php             # Edit user-submitted contact inquiries
├── delete-message.php           # Admin delete message handler
├── reply-message.php            # Community and Admin reply handler for messages
│
├── includes/
│   ├── db.php                   # MySQLi database connection setup
│   ├── functions.php            # Security sanitization, auth guards, file helpers
│   ├── header.php               # Global navigation bar & flash alert messages
│   ├── footer.php               # Global footer & script imports
│   └── mailer.php               # Inquiry mailer and file audit logger
│
├── css/
│   └── style.css                # Custom CSS design system, animations, responsive layout
│
├── js/
│   └── script.js                # Search/filtering, form validation, carousel initialization
│
├── uploads/                     # User uploads directory
│   ├── .htaccess                # Script execution block for uploaded files (Security)
│   ├── avatars/                 # Profile photos directory (.gitkeep)
│   └── projects/                # Project cover and gallery images (.gitkeep)
│
└── logs/                        # System audit logs directory (.gitkeep)
    └── mail_log.txt             # Contact form transmission log
```

---

## 🛡 Security & Validation Implementations

1. **Prepared SQL Statements:** All database queries utilize parameterized `mysqli::prepare` statements to prevent SQL Injection attacks.
2. **XSS Protection:** All dynamic user output rendered in HTML is sanitized using `htmlspecialchars()` with `ENT_QUOTES` and UTF-8 encoding.
3. **Password Security:** Passwords are never stored in plain text; encrypted using PHP `password_hash()` with the `PASSWORD_BCRYPT` algorithm.
4. **Session Authentication Guards:** Protected routes (`dashboard.php`, `settings.php`, `add-project.php`, `edit-project.php`) enforce strict login verification through `requireLogin()`.
5. **Authorization Verification:** Project editing and deletion verify that `$_SESSION['user_id'] === $project['user_id']` so students can only modify their own submissions.
6. **Upload Security:**
   - File extensions validated against strict MIME and type whitelists (`jpg`, `jpeg`, `png`, `webp`).
   - File sizes capped (5MB maximum).
   - Random unique filenames generated via `bin2hex(random_bytes(16))` to prevent file overwrites and path traversal.
   - An `.htaccess` file inside `uploads/` prevents server execution of any uploaded script.
7. **CSRF & Confirmation Protections:** Account deletion enforces password re-authentication, confirmation typing (`DELETE`), and explicit user consent.

---

## 📜 Submission & License

- **Project:** StudentHub Web Platform
- **Course:** ICT 2209 Web Technologies Mini Project
- **Department:** Department of Materials Technology, Faculty of Technology
- **University:** Rajarata University of Sri Lanka
- **Author:** Ayesh Rathnayaka
- **Contact:** `ent2023048@tec.rjt.ac.lk`

*This repository is submitted as a Continuous Assessment (CA) project for academic evaluation.*
