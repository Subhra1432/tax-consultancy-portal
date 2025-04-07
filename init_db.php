<?php
// Database initialization script
echo "<h1>Tax Consultancy Portal Database Setup</h1>";

// Check if PHP can access MySQL
echo "<h2>Checking MySQL connection...</h2>";
$server = 'localhost';
$username = 'root';
$password = ''; // Empty password as per default MySQL setup

// Try connecting to MySQL
try {
    $conn = new mysqli($server, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color:green;'>✓ Connected to MySQL server successfully!</p>";
    
    // Try to create database
    echo "<h2>Creating database...</h2>";
    $sql = "CREATE DATABASE IF NOT EXISTS tax_consultancy";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>✓ Database 'tax_consultancy' created or already exists.</p>";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db("tax_consultancy");
    
    // Load the schema and create tables
    echo "<h2>Creating tables...</h2>";
    $schema_file = __DIR__ . '/includes/schema.sql';
    $schema_content = file_get_contents($schema_file);
    
    // Split the schema into individual queries
    $queries = explode(';', $schema_content);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query) && strpos($query, 'CREATE DATABASE') === false && strpos($query, 'USE ') === false) {
            if ($conn->query($query) === TRUE) {
                if (strpos($query, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?(\w+)/i', $query, $matches);
                    if (isset($matches[1])) {
                        echo "<p style='color:green;'>✓ Table '{$matches[1]}' created or already exists.</p>";
                    }
                }
            } else {
                echo "<p style='color:orange;'>⚠ Query error: " . $conn->error . "</p>";
            }
        }
    }
    
    // Create admin user
    echo "<h2>Setting up admin user...</h2>";
    $check_sql = "SELECT COUNT(*) as count FROM users WHERE username = 'admin'";
    $result = $conn->query($check_sql);
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $password_hash = password_hash("admin123", PASSWORD_DEFAULT);
        $insert_sql = "INSERT INTO users (username, password, email, full_name, role) VALUES ('admin', '$password_hash', 'admin@example.com', 'Admin User', 'admin')";
        
        if ($conn->query($insert_sql) === TRUE) {
            echo "<p style='color:green;'>✓ Admin user created successfully.</p>";
        } else {
            echo "<p style='color:orange;'>⚠ Error creating admin user: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:green;'>✓ Admin user already exists.</p>";
    }
    
    // Setup complete
    echo "<h2>Setup complete!</h2>";
    echo "<p>The database has been initialized successfully.</p>";
    echo "<p>You can now <a href='index.php'>visit the portal</a>.</p>";
    echo "<p>Admin login: <strong>username:</strong> admin, <strong>password:</strong> admin123</p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px; border-radius: 5px;'>";
    echo "<h3>Database Setup Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<h4>Troubleshooting:</h4>";
    echo "<ol>";
    echo "<li>Make sure MySQL server is running: <code>brew services start mysql</code></li>";
    echo "<li>Make sure your MySQL root password is correct</li>";
    echo "<li>If you have a root password set, update the password in this script and in includes/config.php</li>";
    echo "</ol>";
    echo "</div>";
}
?> 