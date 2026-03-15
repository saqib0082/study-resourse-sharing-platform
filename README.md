# StudyVault - Study Resource Sharing Platform

A web-based platform that allows students to upload and share study 
resources, notes, and files with each other.

## Purpose
To make study material easily accessible — students can upload their 
notes and others can browse, preview, and download them in one place.

## Features
- Student registration & login
- Upload and share study notes/files
- Browse and download resources shared by others
- Admin panel to manage users and content
- Role-based access control

## Tech Stack
- PHP (Backend)
- MySQL (Database)
- HTML/CSS (Frontend)
- XAMPP (Local Server)

## Project Structure
study-platform/
│
├── config/
│   ├── db.php              # Database connection
│   ├── sidebar.php         # Sidebar layout
│   └── theme.php           # Theme settings
│
├── api/
│   ├── loginAPI.php        # Login handler
│   ├── registerAPI.php     # Registration handler
│   ├── uploadAPI.php       # File upload handler
│   ├── downloadAPI.php     # File download handler
│   ├── getNotesAPI.php     # Fetch notes
│   ├── deleteNoteAPI.php   # Delete note
│   ├── createUserAPI.php   # Create user (admin)
│   ├── deleteUserAPI.php   # Delete user (admin)
│   ├── updateRoleAPI.php   # Update user role
│   ├── previewAPI.php      # File preview
│   └── logoutAPI.php       # Logout handler
│
├── uploads/                # Uploaded study files
│
├── index.php               # Landing page
├── login.php               # Login page
├── register.php            # Registration page
├── dashboard.php           # Student dashboard
├── notes.php               # Notes listing page
├── upload.php              # Upload page
├── admin.php               # Admin panel
├── admin_login.php         # Admin login
├── autofix.php             # Auto fix utility
└── Setup.sql               # Database setup file

## Setup
1. Install XAMPP
2. Import `Setup.sql` into phpMyAdmin
3. Update `config/db.php` with your database credentials
4. Place project in `htdocs` folder
5. Open `localhost/study-platform` in browser
