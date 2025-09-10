TechSupport Scheduling System
A secure and modern PHP-based scheduling system for managing appointments between technical support agents and customers. The application features a clean, role-based architecture and a user-friendly interface built with Bootstrap 5.
Features
Admin Dashboard: A visual, timeline-based overview of the entire team's daily appointments.
User Management: Admins can easily add, view, and manage agent and other admin accounts.
Appointment Management: Full CRUD (Create, Read, Update, Delete) functionality for appointments.
Secure Authentication: A robust login system with session management and password hashing.
Role-Based Access Control: Separate interfaces and permissions for "Admin" and "Agent" roles, ensuring users only see what they are authorized to see.
Organized Codebase: A modular file structure that separates logic, presentation, and configuration for easy maintenance and scalability.
Project Setup Instructions
Follow these steps to get the project running on your local development machine.
1. Prerequisites
Before you begin, ensure you have the following installed:
A local web server environment like XAMPP (recommended), WAMP, or MAMP.
PHP version 8.0 or newer.
MySQL or MariaDB.
2. Installation Steps
Clone the Repository: Open your terminal or command prompt, navigate to your web server's root directory (e.g., C:/xampp/htdocs), and run the following command:
git clone [https://github.com/your-username/scheduling-system.git](https://github.com/your-username/scheduling-system.git)
Note: Replace your-username/scheduling-system.git with the actual URL of your repository.
Database Setup:
Start the Apache and MySQL services from your XAMPP Control Panel.
Open your web browser and navigate to http://localhost/phpmyadmin/.
Create a new database and name it appointment_system. Use the utf8mb4_general_ci collation.
Select the newly created database from the left-hand sidebar.
Click on the "Import" tab at the top.
Click "Choose File", select the appointment_system.sql file located in the root of the project, and click the "Go" button at the bottom to import the database structure and sample data.
Application Configuration (Crucial Step):
Navigate into the includes/ folder within the project.
You will find a template file named config.template.php.
Create a copy of this file in the same directory and rename the copy to config.php.
Open the new config.php file with a code editor.
Update the DB_USER and DB_PASS values with your local database credentials (for a default XAMPP setup, DB_USER is 'root' and DB_PASS is '').
Ensure the BASE_URL is correct for your local setup (e.g., http://localhost/SchedulingSystem/).
Run the Application:
Open your web browser and navigate to the BASE_URL you defined in your config.php file.
Example: http://localhost/SchedulingSystem/
You should be automatically redirected to the login page.
3. Default Login Credentials
You can log in and test the application using the sample users provided in the .sql file:
Admin Account:
Email: admin@example.com
Password: password123
Agent Account:
Email: agent@example.com
Password: password123