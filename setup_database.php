<?php
require_once 'config.php';

try {
    // Connect to MySQL without selecting database
    $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->exec("USE " . DB_NAME);
    
    echo "Database '" . DB_NAME . "' created successfully.<br>";
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        nrc VARCHAR(20) NOT NULL,
        phone VARCHAR(15) NOT NULL,
        role ENUM('admin', 'officer', 'beneficiary') NOT NULL,
        department VARCHAR(100),
        constituency VARCHAR(100),
        status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
        meta JSON DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    echo "Users table created successfully.<br>";
    
    // Create projects table
    $sql = "CREATE TABLE IF NOT EXISTS projects (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        constituency VARCHAR(100) NOT NULL,
        budget DECIMAL(15,2) NOT NULL,
        status ENUM('planning', 'in_progress', 'completed', 'delayed') DEFAULT 'planning',
        progress INT(3) DEFAULT 0,
        start_date DATE,
        end_date DATE,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    
    $conn->exec($sql);
    echo "Projects table created successfully.<br>";
    
    // Create beneficiary groups table (for group beneficiaries)
    $sql = "CREATE TABLE IF NOT EXISTS beneficiary_groups (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        group_name VARCHAR(255) NOT NULL,
        owner_user_id INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "beneficiary_groups table created successfully.<br>";

    // Create group members table
    $sql = "CREATE TABLE IF NOT EXISTS group_members (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        group_id INT(11) NOT NULL,
        member_name VARCHAR(255) NOT NULL,
        member_phone VARCHAR(20),
        member_nrc VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES beneficiary_groups(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "group_members table created successfully.<br>";
    
    // Create default admin user
    $checkAdmin = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if ($checkAdmin->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, first_name, last_name, nrc, phone, role, department, status) 
                VALUES ('admin', 'admin@cdf.gov.zm', '$hashedPassword', 'System', 'Administrator', '123456/78/9', '0977000000', 'admin', 'ICT', 'active')";
        $conn->exec($sql);
        echo "Default admin user created.<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Admin user already exists.<br>";
    }
    
    echo "<br>Database setup completed successfully!<br>";
    echo '<a href="login.php">Go to Login Page</a>';
    
} catch(PDOException $e) {
    echo "Database setup failed: " . $e->getMessage();
}
?>
