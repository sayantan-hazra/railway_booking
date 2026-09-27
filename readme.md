# RailEase - Railway Ticket Booking System

RailEase is a PHP and MySQL railway ticket-booking application developed as a six-member college project. It provides a complete booking experience for passengers and a management interface for railway administrators, while keeping the workflow simple and practical for everyday use.

## Project Overview

RailEase supports the complete journey from account creation to ticket management:

```text
Sign Up -> Login -> Home -> Search Train -> Select Coach -> Select Seat
-> Select Meal -> Pay from Wallet -> Ticket -> Manage Booking
```

Administrators can manage the railway data and monitor customer activity:

```text
Admin Login -> Manage Trains/Coaches/Seats -> View Bookings
-> Manage Support Tickets
```

## Features

### User Features

- Create an account and securely log in
- Search available trains
- View train and coach information
- Select a coach and available seat
- Select meal preferences during booking
- Pay for bookings using a wallet balance
- View booking confirmation and ticket details
- Manage existing bookings
- Access profile, wallet, and support sections

### Admin Features

- Log in through the admin panel
- View dashboard information
- Add, update, and manage trains
- Manage coaches and seat availability
- View and manage passenger bookings
- Review and manage support tickets

## Technology Stack

| Layer | Technology |
| --- | --- |
| Frontend | HTML, CSS, JavaScript, Bootstrap |
| Backend | PHP |
| Database | MySQL |
| Local environment | XAMPP |
| Web server | Apache |

## Project Structure

```text
railway-booking/
├── admin/                  # Admin dashboard and management pages
├── assets/
│   ├── css/                # Application stylesheets
│   ├── images/             # Images and visual assets
│   └── js/                 # Client-side JavaScript
├── auth/                   # Sign-up, login, and logout pages
├── booking/                # Train search and ticket-booking workflow
├── config/                 # Database configuration
├── includes/               # Shared layout and authentication files
├── user/                   # User home, profile, wallet, and support pages
├── index.php               # Application entry point
└── readme.md               # Project documentation
```

## Requirements

- XAMPP with Apache and MySQL
- PHP supported by the installed XAMPP version
- A modern web browser
- MySQL database configured for the application

## Installation and Setup

1. Install and open XAMPP.
2. Start the **Apache** and **MySQL** modules.
3. Place the project folder inside XAMPP's `htdocs` directory:

	```text
	C:\xampp\htdocs\railway-booking
	```

4. Create the project database in phpMyAdmin.
6. Import `config/schema.sql` to create the tables and a local demonstration admin account.
6. Update the database credentials in `config/db.php` to match your local MySQL setup.
7. Open the application in a browser:

	```text
	http://localhost/railway-booking/
	```

### Admin demonstration login

Open `http://localhost/railway-booking/admin/login.php` after importing the schema:

- Username: `admin`
- Password: `Admin@123`

Change or remove this local demonstration account before deploying outside XAMPP.

## Application Workflow

### Passenger Workflow

1. Register a new account or log in.
2. Search for a train using the available journey details.
3. Choose a train and coach.
4. Select an available seat.
5. Select a meal, if required.
6. Complete payment using the wallet.
7. Review the generated ticket and confirmation.
8. Manage the booking later from the user section.

### Administrator Workflow

1. Log in through the admin section.
2. Maintain train, coach, and seat records.
3. Review passenger bookings.
4. Respond to and manage support tickets.

## Team Contribution

This project is designed and developed by a six-member team. Responsibilities can be distributed across the following areas:

| Area | Responsibility |
| --- | --- |
| Project coordination | Planning, integration, and documentation |
| Frontend development | HTML, CSS, Bootstrap, and responsive layouts |
| JavaScript development | Client-side validation and interactive behavior |
| Backend development | PHP pages, authentication, and application logic |
| Database development | MySQL schema, queries, and data management |
| Testing and deployment | Testing, debugging, XAMPP setup, and deployment checks |

## Project Objective

The objective of RailEase is to demonstrate how a railway reservation platform can be designed using core web technologies. The project brings together user authentication, train and seat management, wallet-based payment, booking confirmation, and administrative support management in one application.

## Academic Project

RailEase is developed for academic purposes as a PHP, MySQL, and cloud-computing college project. It is intended for local demonstration and learning and is not a production railway reservation service.
