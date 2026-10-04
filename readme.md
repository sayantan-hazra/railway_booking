# 🚆 RailEase - Railway Ticket Booking System

RailEase is a PHP and MySQL based Railway Ticket Booking System developed as a six-member college project.

The project provides a complete railway booking workflow for users along with an administrative management system. It includes train searching, coach and seat selection, meal selection, wallet-based payment, ticket generation, booking management, user support, and an admin panel.

> 🎓 **Academic Project - Developed for educational and demonstration purposes.**

---

## 📌 Project Overview

RailEase follows a complete railway ticket booking workflow:

```text
Sign Up
   ↓
Login
   ↓
Home
   ↓
Search Train
   ↓
Select Train
   ↓
Select Coach
   ↓
Select Seat
   ↓
Select Meal
   ↓
Pay from Wallet
   ↓
Generate Ticket / PNR
   ↓
Manage Booking
```

### Admin Workflow

```text
Admin Login
   ↓
Admin Dashboard
   ↓
Manage Trains
   ↓
Manage Coaches
   ↓
Manage Seats
   ↓
View Bookings
   ↓
Manage Support Tickets
```

---

# ✨ Features

## 👤 User Features

- User registration
- Secure user login and logout
- User profile
- Search trains
- View train details
- Select coach
- Select available seat
- Select meal
- Wallet-based payment
- Booking confirmation
- PNR generation
- Ticket generation
- View booking history
- Manage bookings
- Cancel bookings
- Wallet balance
- Wallet transactions
- Customer support

---

## 👑 Admin Features

- Admin login
- Admin dashboard
- Train management
- Coach management
- Seat management
- View passenger bookings
- Manage railway data
- Manage support tickets
- Monitor user activity
- Database administration

---

# 💻 Technology Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript, Bootstrap |
| Backend | PHP |
| Database | MySQL |
| Database Management | phpMyAdmin |
| Local Server | XAMPP |
| Web Server | Apache |
| Version Control | Git |
| Repository | GitHub |

---

# 🗄️ Database

The project uses MySQL with the following database:

```text
railway_booking
```

The database contains tables for:

- Users
- Stations
- Trains
- Train Stops
- Coaches
- Seats
- Meals
- Bookings
- Wallets
- Wallet Transactions
- Support Tickets

The database schema is maintained inside the project repository.

---

# 📂 Project Structure

```text
railway-booking/
│
├── admin/
│   └── Admin dashboard and management pages
│
├── assets/
│   ├── css/
│   ├── images/
│   └── js/
│
├── auth/
│   ├── Login
│   ├── Signup
│   └── Logout
│
├── booking/
│   └── Train search and booking workflow
│
├── config/
│   ├── db.php
│   └── schema.sql
│
├── includes/
│   └── Shared PHP files
│
├── user/
│   ├── Profile
│   ├── Wallet
│   ├── Bookings
│   └── Support
│
├── index.php
└── readme.md
```

---

# ⚙️ Requirements

Before running RailEase, install:

- XAMPP
- Apache
- MySQL
- PHP
- phpMyAdmin
- Modern web browser
- Git (optional)

---

# 🚀 Installation & Setup

## 1. Install XAMPP

Download and install XAMPP.

Open the XAMPP Control Panel and start:

```text
Apache
MySQL
```

---

## 2. Place the Project in htdocs

Copy the project folder into:

```text
C:\xampp\htdocs\
```

For example:

```text
C:\xampp\htdocs\railway-booking
```

---

## 3. Create the Database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin/
```

Create a database named:

```text
railway_booking
```

---

## 4. Import the SQL File

Open the `railway_booking` database in phpMyAdmin.

Go to:

```text
Import
```

Select:

```text
config/schema.sql
```

and import it.

The SQL file creates the required tables and development/demo data.

---

## 5. Configure Database Connection

Open:

```text
config/db.php
```

Configure the database according to your XAMPP setup.

Typical XAMPP configuration:

```text
Host: localhost
Username: root
Password:
Database: railway_booking
```

---

## 6. Run the Project

Open your browser and visit:

```text
http://localhost/railway-booking/
```

---

# 🔐 Admin Login

For local academic demonstration:

```text
Username: admin
Password: Admin@123
```

Admin login page:

```text
http://localhost/railway-booking/admin/login.php
```

> ⚠️ The above credentials are intended only for local development and academic demonstration. Change or remove the demo account before deploying the project publicly.

---

# 🔄 Application Workflow

## Passenger Workflow

1. Register a new account.
2. Login to the system.
3. Search for available trains.
4. Select the desired train.
5. Select a coach.
6. Select an available seat.
7. Select a meal preference.
8. Pay using the wallet.
9. Generate the booking confirmation and PNR.
10. View or print the ticket.
11. Manage the booking from the user section.

---

## Administrator Workflow

1. Login through the admin panel.
2. Access the admin dashboard.
3. Manage train information.
4. Manage coaches.
5. Manage seats.
6. View passenger bookings.
7. Manage support tickets.
8. Maintain railway-related data.

---

# 👥 Team Contribution

RailEase was developed by a six-member team.

The project follows a modular development approach where every member was assigned specific responsibilities.

However, the **overall project concept, architecture, database, administration system, repository management, integration, and technical direction were led by the Project Leader.**

---

## 👑 1. Sayantan Hazra (L)

### Project Leader & Lead Developer

**Major Responsibilities:**

- 💡 Original project idea and overall concept
- 👑 Project leadership
- 🧠 Overall system planning and architecture
- 🗄️ Complete database design
- 💾 SQL schema development
- 👑 Complete Admin Panel development
- 🔐 Admin authentication and authorization
- 🚆 Railway/train data management
- 🪑 Coach and seat data management
- 🗃️ Database relationships and queries
- 🔧 Git and GitHub repository management
- 🔀 Code integration
- 🧪 Database testing
- 🐛 Overall debugging
- 🔧 Technical troubleshooting
- 🤝 Technical assistance to team members
- 📋 Overall project structure and organization
- 🚀 Final project integration
- 📌 Overall supervision of the project

### Leadership Contribution

> The overall idea and technical direction of RailEase were initiated and led by **Sayantan Hazra (L)**. He was responsible for the overall architecture, database and SQL implementation, complete administration system, Git/GitHub management, integration of team modules, technical guidance, and final supervision of the project.

---

# 🎨 2. Sayantan Pal

### Frontend & Booking Flow Developer

**Responsibilities:**

- 🏠 Homepage UI development
- 🎨 Frontend design
- 🔎 Train search interface
- 🚆 Train selection interface
- 🪑 Coach selection
- 💺 Seat selection
- 🍱 Meal selection
- 🎟️ Booking workflow
- 🎫 Ticket generation workflow
- 🧪 Frontend testing
- 🤝 Support during project integration

---

# 🎨 3. Sayan Sur

### Frontend & Booking Flow Developer

**Responsibilities:**

- 🏠 Homepage UI development
- 🎨 User interface implementation
- 🔎 Train search
- 🚆 Train selection workflow
- 🪑 Coach selection
- 💺 Seat selection
- 🍱 Meal selection
- 🎟️ Search-to-booking workflow
- 🎫 Ticket generation
- 🧪 Testing and debugging
- 🤝 Frontend integration support

---

# 🔐 4. Saikat Jana

### Authentication & Booking Management Developer

**Responsibilities:**

- 🔑 Login system
- 📝 Signup system
- 🚪 Logout functionality
- 🔐 Authentication
- 👤 Session management
- 🎫 Manage Booking
- 📋 Booking history
- ❌ Booking cancellation
- 🧪 Authentication testing
- 🐛 Debugging authentication and booking-management modules
- 🤝 Integration support

---

# 👤 5. Rupankar Sarkar

### Profile, Wallet & Documentation

**Responsibilities:**

- 👤 User profile page
- ✏️ Profile management
- 💰 Wallet interface
- 💳 Wallet balance
- 📊 Wallet transaction display
- 📑 PPT preparation
- 📝 Project documentation support
- 🧪 Testing
- 🤝 Assistance to other team members
- 🎤 Presentation support

---

# 🎫 6. Rupak Patra

### Support & Ticket Developer

**Responsibilities:**

- 🆘 Support page
- 💬 Customer support functionality
- 🎫 Ticket printout
- 🧾 Ticket formatting
- 📄 Ticket details display
- 🖨️ Print-friendly ticket
- 🧪 Testing
- 🐛 Debugging
- 🤝 Assistance to other team members

---

# 📊 Team Contribution Summary

| Member | Role | Main Contribution |
|---|---|---|
| 👑 **Sayantan Hazra (L)** | **Project Leader & Lead Developer** | **Project idea, architecture, Admin, Database/SQL, Git/GitHub, integration, supervision** |
| **Sayantan Pal** | Frontend & Booking | Homepage + Train Search → Booking → Ticket |
| **Sayan Sur** | Frontend & Booking | Homepage + Train Search → Booking → Ticket |
| **Saikat Jana** | Authentication | Login + Signup + Manage Booking |
| **Rupankar Sarkar** | User Module | Profile + Wallet + PPT + Team Support |
| **Rupak Patra** | Support Module | Support Page + Ticket Printout + Team Support |

---

# 🤝 Collaboration

Although each member had specific responsibilities, the project was developed collaboratively.

Team members helped each other with:

- Debugging
- Testing
- PHP integration
- Database connectivity
- UI integration
- Feature integration
- Presentation
- Documentation
- Final project testing

The Project Leader coordinated the overall development and integrated the individual modules into the final RailEase application.

---

# 🔀 Git & GitHub

Git and GitHub were used for source-code management and collaboration.

Repository:

```text
https://github.com/sayantan-hazra/railway_booking
```

General development workflow:

```text
Develop Module
      ↓
Test Module
      ↓
Git Commit
      ↓
Git Push
      ↓
GitHub
      ↓
Integration
      ↓
Final Testing
```

The main repository and overall Git/GitHub management are maintained by:

**Sayantan Hazra (L)**

---

# 🎯 Project Objective

The objective of RailEase is to demonstrate the development of a railway reservation system using fundamental web-development technologies.

The project combines:

- User authentication
- Train searching
- Coach management
- Seat selection
- Meal selection
- Wallet payment
- Ticket generation
- PNR generation
- Booking management
- Customer support
- Administrative management
- MySQL database operations

into a single web application.

---

# 🔮 Future Improvements

Possible future improvements include:

- 💳 Online payment gateway
- 📧 Email ticket notifications
- 📱 SMS notifications
- 🔐 OTP verification
- 🚆 Live train status
- 🗺️ Real-time train tracking
- 🎫 QR-code tickets
- 👥 Multiple passengers per booking
- ⏳ Waiting-list management
- 🤖 Automatic seat allocation
- 📊 Advanced admin analytics
- 🔌 Railway API integration
- ☁️ Cloud deployment

---

# ⚠️ Disclaimer

RailEase is an **academic/college project** created for learning and demonstration purposes.

It is **not affiliated with Indian Railways or IRCTC** and must not be used as an actual railway reservation service.

All sample trains, stations, users, bookings, and other data are intended for development and demonstration purposes only.

---

# 👨‍💻 Development Team

### 👑 Project Leader

**Sayantan Hazra (L)**

### Team Members

- **Sayantan Hazra (L)** — Project Leader & Lead Developer
- **Sayantan Pal** — Frontend & Booking Flow
- **Sayan Sur** — Frontend & Booking Flow
- **Saikat Jana** — Authentication & Manage Booking
- **Rupankar Sarkar** — Profile, Wallet & Documentation
- **Rupak Patra** — Support & Ticket Printout

---

# 🚆 RailEase

### Search. Book. Manage. Travel.

**A collaborative academic project led and coordinated by Sayantan Hazra (L).**
