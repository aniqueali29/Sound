<?php
// File: admin/pages/auth_check.php
session_start();

// Database connection function
function get_db_connection() {
    // Database credentials - adjust these to match your config
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sound";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Check if admin is logged in
function check_admin_auth() {
    // Check if admin session exists
    if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
        // Redirect to login page
        header("Location: auth/login.php");
        exit();
    }
    
    return true;
}

// Get admin details
function get_admin_details() {
    if (!check_admin_auth()) {
        return false;
    }
    
    // Get database connection
    $conn = get_db_connection();
    
    $admin_id = $_SESSION['admin_id'];
    $admin_details = [];
    
    // Query to get admin details including profile picture
    $sql = "SELECT id, username, name, email, profile_picture FROM admins 
            WHERE id = ? AND deleted_at IS NULL";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin_details = $result->fetch_assoc();
            
            // Set default profile picture if none exists
            if (empty($admin_details['profile_picture'])) {
                $admin_details['profile_picture'] = '../assets/images/default-avatar.png';
            }
        } else {
            // Admin not found or deleted, destroy session
            session_unset();
            session_destroy();
            header("Location: auth/login.php");
            exit();
        }
        
        $stmt->close();
    }
    
    $conn->close();
    return $admin_details;
}

// Check admin authentication
$admin_logged_in = check_admin_auth();

// Get admin details if logged in
$admin = get_admin_details();
?>