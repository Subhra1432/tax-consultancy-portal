<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get current subscription
$stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subscription = $stmt->get_result()->fetch_assoc();

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Calculate subscription amount with GST
$base_amount = SUBSCRIPTION_AMOUNT;
$gst_amount = ($base_amount * GST_PERCENTAGE) / 100;
$total_amount = $base_amount + $gst_amount;

// Handle payment success
if (isset($_GET['payment_id']) && isset($_GET['payment_status']) && $_GET['payment_status'] === 'success') {
    $payment_id = $_GET['payment_id'];
    
    // Update subscription
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+1 month'));
    
    $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, status, start_date, end_date, amount, payment_id) VALUES (?, 'active', ?, ?, ?, ?)");
    $stmt->bind_param("issds", $user_id, $start_date, $end_date, $total_amount, $payment_id);
    
    if ($stmt->execute()) {
        header("Location: subscription.php?success=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription - Tax Consultancy Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Tax Consultancy Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="documents.php">Documents</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">Appointments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">Messages</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item active" href="subscription.php">Subscription</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                Your subscription has been successfully activated!
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Subscription Management</h3>
                        
                        <?php if ($subscription && $subscription['status'] == 'active'): ?>
                            <div class="alert alert-success">
                                <h5>Active Subscription</h5>
                                <p>Your subscription is active until <?php echo date('d M Y', strtotime($subscription['end_date'])); ?></p>
                                <p>Amount paid: ₹<?php echo number_format($subscription['amount'], 2); ?></p>
                            </div>
                        <?php elseif ($subscription && $subscription['status'] == 'trial'): ?>
                            <div class="alert alert-warning">
                                <h5>Trial Period</h5>
                                <p>Your trial period ends on <?php echo date('d M Y', strtotime($subscription['end_date'])); ?></p>
                                <p>Subscribe now to continue accessing our services!</p>
                            </div>
                        <?php endif; ?>

                        <div class="subscription-plans">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <h4>Monthly Subscription</h4>
                                    <h2 class="text-primary my-4">₹<?php echo number_format($base_amount, 2); ?></h2>
                                    <p class="text-muted">+ 18% GST (₹<?php echo number_format($gst_amount, 2); ?>)</p>
                                    <hr>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">✓ Unlimited Document Upload</li>
                                        <li class="mb-2">✓ Expert Consultation</li>
                                        <li class="mb-2">✓ Priority Support</li>
                                        <li class="mb-2">✓ Secure Document Storage</li>
                                    </ul>
                                    <?php if (!$subscription || $subscription['status'] != 'active'): ?>
                                        <button class="btn btn-primary btn-lg mt-3" onclick="startPayment()">Subscribe Now</button>
                                    <?php endif; ?>
                                </div>
                            </div>
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
    <script>
        function startPayment() {
            var options = {
                "key": "<?php echo RAZORPAY_KEY_ID; ?>",
                "amount": "<?php echo $total_amount * 100; ?>", // Amount in paise
                "currency": "INR",
                "name": "Tax Consultancy Portal",
                "description": "Monthly Subscription",
                "handler": function (response) {
                    window.location.href = "subscription.php?payment_id=" + response.razorpay_payment_id + "&payment_status=success";
                },
                "prefill": {
                    "name": "<?php echo htmlspecialchars($user['full_name']); ?>",
                    "email": "<?php echo htmlspecialchars($user['email']); ?>",
                    "contact": "<?php echo htmlspecialchars($user['phone']); ?>"
                },
                "theme": {
                    "color": "#0d6efd"
                }
            };
            var rzp = new Razorpay(options);
            rzp.open();
        }
    </script>
</body>
</html> 