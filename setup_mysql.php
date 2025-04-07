<?php
echo '<!DOCTYPE html>
<html>
<head>
    <title>MySQL Configuration Guide</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        code {
            background: #f4f4f4;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .instruction {
            border-left: 5px solid #0275d8;
            padding-left: 15px;
            margin: 20px 0;
        }
        h1, h2, h3 {
            color: #333;
        }
        .alert {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>MySQL Configuration Guide</h1>
    <p>This guide will help you set up MySQL correctly for the Tax Consultancy Portal.</p>
    
    <div class="alert">
        <h3>Current Error</h3>
        <p>You are seeing an authentication error when trying to connect to MySQL. This is because MySQL is installed with security defaults that need to be configured.</p>
    </div>
    
    <h2>Option 1: Reset MySQL Root Password (Recommended)</h2>
    <div class="instruction">
        <p>Run the following commands in your terminal:</p>
        <pre>
# Stop MySQL server
brew services stop mysql

# Start MySQL with skip-grant-tables
sudo mysqld_safe --skip-grant-tables &

# Connect to MySQL (no password needed now)
mysql -u root

# Now in the MySQL console, run:
FLUSH PRIVILEGES;
ALTER USER \'root\'@\'localhost\' IDENTIFIED BY \'\';
FLUSH PRIVILEGES;
EXIT;

# Stop and restart MySQL normally
sudo killall mysqld
brew services start mysql
        </pre>
        <p>This will reset your root password to empty.</p>
    </div>
    
    <h2>Option 2: Update MySQL Configuration in the Portal</h2>
    <div class="instruction">
        <p>If you know your MySQL root password or prefer to use it, you can update the configuration file:</p>
        <p>Edit <code>includes/config.php</code> and update line 5 with your password:</p>
        <pre>define(\'DB_PASSWORD\', \'your_actual_password\');</pre>
    </div>
    
    <h2>Option 3: Create a New MySQL User</h2>
    <div class="instruction">
        <p>If you know your root password, you can create a new user with appropriate permissions:</p>
        <pre>
# Connect to MySQL with your root password
mysql -u root -p

# Then in the MySQL console:
CREATE USER \'taxportal\'@\'localhost\' IDENTIFIED BY \'taxportal123\';
GRANT ALL PRIVILEGES ON tax_consultancy.* TO \'taxportal\'@\'localhost\';
FLUSH PRIVILEGES;
EXIT;
        </pre>
        <p>Then edit <code>includes/config.php</code> with these new credentials:</p>
        <pre>
define(\'DB_USERNAME\', \'taxportal\');
define(\'DB_PASSWORD\', \'taxportal123\');
        </pre>
    </div>
    
    <h2>Option 4: Use Password Authentication</h2>
    <div class="instruction">
        <p>Run MySQL secure installation to properly set up passwords:</p>
        <pre>mysql_secure_installation</pre>
        <p>Follow the prompts to set a root password and secure your MySQL installation.</p>
        <p>Then edit <code>includes/config.php</code> with your new root password.</p>
    </div>
    
    <h2>After Configuration</h2>
    <p>Once you have configured MySQL, visit <a href="init_db.php">the database initialization page</a> to set up the database tables.</p>
</body>
</html>';
?> 