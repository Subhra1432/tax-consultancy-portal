<?php
session_start();

// Check for database connection before loading the main page
try {
    // Attempt to connect to MySQL
    $conn = @mysqli_connect('localhost', 'root', '');
    
    if (!$conn) {
        // If connection fails, redirect to setup page
        header("Location: setup_mysql.php");
        exit();
    }
    
    // Try to select the database
    if (!@mysqli_select_db($conn, 'tax_consultancy')) {
        header("Location: init_db.php");
        exit();
    }
    
    // If we got here, the connection is successful, so load the config
    require_once 'includes/config.php';
    
} catch (Exception $e) {
    // If there's any error, redirect to setup
    header("Location: setup_mysql.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Consultancy Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Tax Consultancy Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2 text-center">
                <h1>Welcome to Tax Consultancy Portal</h1>
                <p class="lead">Professional tax consultation services at your fingertips</p>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h3>Monthly Subscription</h3>
                        <h2 class="text-primary">₹200</h2>
                        <p>+ 18% GST</p>
                        <ul class="list-unstyled">
                            <li>✓ Unlimited Document Upload</li>
                            <li>✓ Expert Consultation</li>
                            <li>✓ Priority Support</li>
                        </ul>
                        <a href="register.php" class="btn btn-primary">Get Started</a>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <h3>Our Services</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="service-item">
                            <h4>Document Management</h4>
                            <p>Securely store and manage all your tax-related documents</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="service-item">
                            <h4>Expert Consultation</h4>
                            <p>Get advice from experienced tax professionals</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="service-item">
                            <h4>Appointment Scheduling</h4>
                            <p>Book appointments with tax experts at your convenience</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="service-item">
                            <h4>Secure Messaging</h4>
                            <p>Communicate directly with your assigned tax expert</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p>&copy; 2024 Tax Consultancy Portal. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 