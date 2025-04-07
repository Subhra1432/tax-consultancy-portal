<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Active subscriptions
$result = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
$stats['active_subscriptions'] = $result->fetch_assoc()['count'];

// Trial users
$result = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'trial'");
$stats['trial_users'] = $result->fetch_assoc()['count'];

// Expired subscriptions
$result = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'expired'");
$stats['expired_subscriptions'] = $result->fetch_assoc()['count'];

// Recent users
$stmt = $conn->prepare("
    SELECT u.*, s.status as subscription_status, s.end_date 
    FROM users u 
    LEFT JOIN subscriptions s ON u.id = s.user_id 
    WHERE u.role = 'user' 
    ORDER BY u.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent payments
$stmt = $conn->prepare("
    SELECT s.*, u.username, u.email 
    FROM subscriptions s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.payment_id IS NOT NULL 
    ORDER BY s.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Tax Consultancy Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="subscriptions.php">Subscriptions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="documents.php">Documents</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p class="text-muted">Total Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <h3><?php echo $stats['active_subscriptions']; ?></h3>
                        <p class="text-muted">Active Subscriptions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <h3><?php echo $stats['trial_users']; ?></h3>
                        <p class="text-muted">Trial Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <h3><?php echo $stats['expired_subscriptions']; ?></h3>
                        <p class="text-muted">Expired Subscriptions</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Recent Users</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $user['subscription_status'] == 'active' ? 'success' : 
                                                        ($user['subscription_status'] == 'trial' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($user['subscription_status'] ?? 'none'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="users.php" class="btn btn-outline-primary btn-sm">View All Users</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Recent Payments</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['username']); ?></td>
                                            <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo date('d M Y', strtotime($payment['created_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-success">Paid</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="subscriptions.php" class="btn btn-outline-primary btn-sm">View All Payments</a>
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