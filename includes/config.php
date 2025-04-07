<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Your MySQL root password is blank by default
define('DB_NAME', 'tax_consultancy');

// Attempt to connect to MySQL database - with error handling improvements
try {
    // First try to connect to server only
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);
    
    if (!$conn) {
        throw new Exception("Could not connect to MySQL server: " . mysqli_connect_error());
    }
    
    // Then try to create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    mysqli_query($conn, $sql);
    
    // Now select the database
    if (!mysqli_select_db($conn, DB_NAME)) {
        throw new Exception("Could not select database: " . mysqli_error($conn));
    }
    
    // Now try to create tables
    $schema_lines = file(__DIR__ . '/schema.sql', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $query = '';
    
    // Skip the first two lines which have the CREATE DATABASE and USE statements
    for ($i = 2; $i < count($schema_lines); $i++) {
        $line = $schema_lines[$i];
        
        // Skip comments and empty lines
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        $query .= $line;
        
        // If the line ends with a semicolon, execute the query
        if (substr(trim($line), -1, 1) == ';') {
            mysqli_query($conn, $query);
            $query = '';
        }
    }
    
} catch (Exception $e) {
    // Display user-friendly error message
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px; border-radius: 5px;'>";
    echo "<h3>Configuration Error</h3>";
    echo "<p>There was an error connecting to the database. Please check your configuration or contact the administrator.</p>";
    echo "<p>Error details (for administrator): " . $e->getMessage() . "</p>";
    echo "</div>";
    
    // Log the actual error for administrators
    error_log("Database connection error: " . $e->getMessage());
    exit();
}

// Razorpay configuration
define('RAZORPAY_KEY_ID', 'YOUR_RAZORPAY_KEY_ID');
define('RAZORPAY_KEY_SECRET', 'YOUR_RAZORPAY_KEY_SECRET');

// Subscription amount
define('SUBSCRIPTION_AMOUNT', 200); // ₹200 per month
define('GST_PERCENTAGE', 18); // 18% GST
?> 