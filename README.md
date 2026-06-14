# Document Management System Guide

## Overview
This Document Management System is currently a static front-end web application. It is designed to track documents and manage user access across different categories (General, Registrar, and Admin). 

The project is structured into two main components:
1. **Login Form** (`/login form`)
2. **Management Dashboard** (`/management`)

---

## Current Architecture (Frontend-Only)

### 1. Login Module
- **`login.html`**: The main authentication page containing the login form, social login links, and navigation to registration.
- **`register.html`**: The page for new users to create an account.
- **`style.css`**: Contains the visual styling for the authentication pages, including background effects and form layouts.

### 2. Document Tracker Module
- **`document-track.html`**: The primary dashboard interface ("Docker-Up"). It features:
  - A sidebar with navigation categories: *All Documents*, *General Docs*, *Registrar Only*, and *Admin Only*.
  - A main content area displaying a list of shared documents in a tabular format.
  - Mock data for documents like PDFs and Excel files with different permission levels.
- **`document-track.css`**: Styles specific to the dashboard interface, managing the layout, tables, sidebars, and badges.
- **`document-track.js`**: Contains the client-side interactivity, including:
  - Real-time search filtering based on document details.
  - Category filtering using the sidebar navigation to show/hide specific document rows.

---

## Planning the Transition to PHP (Backend Integration)

Currently, the application relies on hardcoded HTML and client-side JavaScript. To make this a fully functional, dynamic application, migrating to a server-side language like **PHP** alongside a database (like **MySQL**) is highly recommended. 

Here is a step-by-step plan for transitioning to PHP:

### Phase 1: Environment Setup
1. **Install a Local Server Environment**: Install XAMPP, WAMP, or Laragon on your development machine to run PHP and MySQL locally.
2. **File Conversion**: Rename all `.html` files to `.php` (e.g., `login.html` becomes `login.php`, `document-track.html` becomes `document-track.php`).

### Phase 2: Database Design (MySQL)
Create a database with the following core tables:
- **`users`**: To store `id`, `username`, `password` (hashed), `role` (Admin, Registrar, General), and timestamps.
- **`documents`**: To store `id`, `title`, `description`, `filename`, `file_path`, `category_id`, `uploaded_by`, and `upload_date`.
- **`categories`**: To define document categories (General Docs, Registrar Only, Admin Only).

### Phase 3: Implementing Authentication
1. **Form Handling**: Update `login.php` and `register.php` forms to `method="POST"`.
2. **User Registration**: Process the registration form using PHP, hash the passwords using `password_hash()`, and store users in the database securely using PDO or MySQLi prepared statements.
3. **Session Management**: Upon successful login, start a PHP session (`session_start()`) and store the user's ID and role in `$_SESSION`.
4. **Access Control**: Add a PHP check at the top of `document-track.php` to redirect unauthorized users back to the login page if they are not logged in.

### Phase 4: Dynamic Document Tracking
1. **File Uploads**: Implement a PHP script to handle document uploads securely. Move uploaded files to a protected directory on the server and save their metadata (name, path, category, uploader) in the `documents` table.
2. **Fetching Data**: Replace the hardcoded table rows in `document-track.php` with a PHP script that queries the database and dynamically generates the HTML rows based on the available documents.
3. **Role-Based Access**: Modify the database query to only fetch documents that the logged-in user is authorized to see (e.g., a "General" user should not see "Admin Only" documents).
4. **Dynamic Filtering**: You can keep the existing `document-track.js` for fast client-side filtering, or move the search/filter logic to the backend using PHP and AJAX for better security and performance with large datasets.

### Phase 5: Security Enhancements
- **SQL Injection Prevention**: Strictly use Prepared Statements for all database queries.
- **XSS Protection**: Use `htmlspecialchars()` when outputting any user-generated content (like document names or descriptions) in your PHP files.
- **File Security**: Ensure the uploads directory does not execute PHP scripts (e.g., by placing an `.htaccess` file denying execution) and validate file types (e.g., only allow `.pdf`, `.docx`, `.xlsx`) before uploading.

### Final Note
Make sure that the system will work without error or bugs because the development focuses on HTML, CSS, JavaScript, and PHP, and utilizes XAMPP and MySQL for the backend environment.
