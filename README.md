# Library Management System

A PHP & MySQL-based Library Management System with separate Admin and Student portals, book management, issue/return tracking, fine calculation, and QR code scanning.

## Project Overview

The Library Management System is a web-based DBMS project developed using PHP, MySQL, HTML, CSS, and JavaScript.

The system provides separate functionality for administrators and students.

### Admin Portal

The administrator can:

- Manage books
- Add, edit and delete books
- Manage authors
- Manage categories
- Manage student members
- Issue books
- Return books
- Calculate fines
- View issued book records
- Generate QR codes for books
- Scan QR codes to identify books
- View library statistics

### Student Portal

Students can:

- View the dashboard
- Browse available books
- View detailed book information
- View currently issued books
- View complete borrowing history
- View account information
- Scan book QR codes

## Technologies Used

- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- XAMPP
- phpMyAdmin
- QR Code
- Git & GitHub

## Main Features

### Authentication

- Student registration
- User login
- Admin login
- Password hashing
- Role-based access
- Session management
- Logout

### Book Management

- Add books
- Edit books
- Delete books
- Search books
- Track total quantity
- Track available quantity
- Author management
- Category management
- Shelf number tracking

### Issue and Return Management

- Issue books to students
- Set issue date
- Set due date
- Return books
- Track returned books
- Prevent issuing unavailable books
- Prevent duplicate active issues
- Calculate overdue fines

### Fine Calculation

The system calculates a fine of:

**₹10 per overdue day**

### QR Code System

Each book can have a QR code containing its book code.

The system supports:

- QR code generation
- QR code download
- Camera QR scanning
- Gallery/image QR scanning
- Book identification through QR code
- Direct navigation to the book issue page

## User Roles

### Admin

The admin has access to:

- Dashboard
- Books
- Categories
- Authors
- Members
- Issue/Return
- QR Books
- QR Scanner

### Student

Students have access to:

- Dashboard
- Browse Books
- My Issued Books
- Book History
- My Account
- Logout

## Database

The project uses MySQL database:

```text
library_management