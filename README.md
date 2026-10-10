# Fathom Customer & Project Management Portal (Task 12 - Final Project)

## 📌 Project Overview
Fathom is a secure, full-stack enterprise web application designed to streamline customer interactions, handle internal service inquiries, and manage client projects with strict Role-Based Access Control (RBAC). This project unifies all core modules developed across previous milestones into a single, cohesive architecture.

## 🚀 Main Features
- **Secure Authentication System:** Session-based user registration and login with password hashing (`password_hash` & `password_verify`).
- **Role-Based Access Control (RBAC):** Distinct privileges for `admin` and `regular_employee` roles protecting sensitive API endpoints and UI actions.
- **Client Project Management (CRUD):** Complete lifecycle management for client projects (Create, Read, Update status, Delete).
- **Interactive Dashboard:** Real-time metrics tracking total projects, active workloads, and completion rates.
- **RESTful API Architecture:** Secure backend endpoints communicating via standard HTTP methods (`GET`, `POST`, `PUT`, `DELETE`).

## 🛠️ Technologies Used
- **Frontend:** HTML5, CSS3 (Modern Design System), JavaScript (ES6+ Fetch API).
- **Backend:** PHP (Object-Oriented PDO database interactions).
- **Database:** MySQL.
- **Server Environment:** Apache / XAMPP.

## ⚙️ Setup & Installation Instructions
1. **Clone or Download** the project into your local server root directory (e.g., `htdocs/fathom` in XAMPP).
2. **Configure Database:**
   - Open phpMyAdmin and create a database named `fathom_db` (or match your `db.php` configuration).
   - Import the required SQL schema files (`database/projects.sql` and users tables).
3. **Database Connection:** Ensure `api/db.php` has the correct database credentials (`host`, `dbname`, `username`, `password`).

## ▶️ How to Run the Project
1. Start Apache and MySQL services from your XAMPP/WAMP control panel.
2. Open your web browser and navigate to:
   `http://localhost/fathom/auth.html`
3. Register a new account or log in with an existing administrator account.
4. Access the **Dashboard** or **Projects** page (`projects.html`) to manage workflows, track statuses, and interact with the system.