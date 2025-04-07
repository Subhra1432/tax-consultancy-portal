<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get subscription information
$stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subscription = $stmt->get_result()->fetch_assoc();

// Get documents count
$stmt = $conn->prepare("SELECT COUNT(*) as doc_count FROM documents WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doc_count = $stmt->get_result()->fetch_assoc()['doc_count'];

// Get uploaded documents count for this month
$stmt = $conn->prepare("SELECT COUNT(*) as uploaded_this_month FROM documents WHERE user_id = ? AND MONTH(uploaded_at) = MONTH(CURRENT_DATE()) AND YEAR(uploaded_at) = YEAR(CURRENT_DATE())");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$uploaded_this_month = $stmt->get_result()->fetch_assoc()['uploaded_this_month'];

// Get unread messages count
$stmt = $conn->prepare("SELECT COUNT(*) as msg_count FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_messages = $stmt->get_result()->fetch_assoc()['msg_count'];

// Get pending actions count (example: documents needing review)
$stmt = $conn->prepare("SELECT COUNT(*) as action_count FROM appointments WHERE user_id = ? AND status = 'pending' AND appointment_date > NOW()");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_actions = $stmt->get_result()->fetch_assoc()['action_count'];

// Get upcoming appointments
$stmt = $conn->prepare("SELECT COUNT(*) as appointment_count FROM appointments WHERE user_id = ? AND appointment_date > NOW() AND status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointment_count = $stmt->get_result()->fetch_assoc()['appointment_count'];

// Get recent documents
$stmt = $conn->prepare("SELECT * FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_docs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tax Consultancy Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .sidebar {
            background-color: white;
            height: 100vh;
            position: sticky;
            top: 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            padding: 20px 0;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #666;
            text-decoration: none;
            border-left: 3px solid transparent;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background-color: #f8f9fa;
            color: #0d6efd;
            border-left: 3px solid #0d6efd;
        }
        .sidebar-link i {
            margin-right: 10px;
            font-size: 20px;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .stats-card {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .stats-card h1 {
            font-size: 3rem;
            font-weight: bold;
            margin: 10px 0;
            color: #0d6efd;
        }
        .stats-card p {
            color: #6c757d;
            margin: 0;
        }
        .card-icon {
            font-size: 24px;
            color: #6c757d;
            display: inline-block;
            float: right;
        }
        .doc-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        .doc-item:last-child {
            border-bottom: none;
        }
        .doc-icon {
            width: 40px;
            height: 40px;
            background-color: #e9ecef;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .doc-info {
            flex: 1;
        }
        .doc-info h6 {
            margin: 0;
            font-weight: 600;
        }
        .doc-info small {
            color: #6c757d;
        }
        .doc-action {
            margin-left: 15px;
        }
        .task-item {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            border-left: 3px solid #0d6efd;
        }
        .task-item h6 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        .task-item small {
            color: #6c757d;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .theme-toggle {
            cursor: pointer;
            font-size: 1.5rem;
            color: #6c757d;
        }
        .profile-dropdown img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }
        .status-badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="text-center mb-4">
                    <h5>Ut Corporate Services</h5>
                    <p class="text-muted">Client Portal</p>
                </div>
                
                <div class="pt-3">
                    <a href="dashboard.php" class="sidebar-link active">
                        <i class="bi bi-grid"></i> Dashboard
                    </a>
                    <a href="documents.php" class="sidebar-link">
                        <i class="bi bi-file-earmark-text"></i> View Documents
                    </a>
                    <a href="upload.php" class="sidebar-link">
                        <i class="bi bi-cloud-upload"></i> Upload Documents
                    </a>
                    <a href="messages.php" class="sidebar-link">
                        <i class="bi bi-chat-left-text"></i> Messages
                        <?php if ($unread_messages > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?php echo $unread_messages; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="appointments.php" class="sidebar-link">
                        <i class="bi bi-calendar-check"></i> Schedule Appointment
                    </a>
                    <a href="profile.php" class="sidebar-link">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a href="../logout.php" class="sidebar-link mt-5">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <div class="theme-toggle">
                            <i class="bi bi-sun"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="d-flex justify-content-between w-100">
                                <span>Documents</span>
                                <i class="bi bi-file-earmark card-icon"></i>
                            </div>
                            <h1><?php echo $doc_count; ?></h1>
                            <p><?php echo $uploaded_this_month; ?> uploaded this month</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="d-flex justify-content-between w-100">
                                <span>Pending Actions</span>
                                <i class="bi bi-clock-history card-icon"></i>
                            </div>
                            <h1><?php echo $pending_actions; ?></h1>
                            <p>1 due this week</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="d-flex justify-content-between w-100">
                                <span>Messages</span>
                                <i class="bi bi-chat-left card-icon"></i>
                            </div>
                            <h1><?php echo $unread_messages; ?></h1>
                            <p>Unread messages</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="d-flex justify-content-between w-100">
                                <span>Appointments</span>
                                <i class="bi bi-calendar card-icon"></i>
                            </div>
                            <h1><?php echo $appointment_count; ?></h1>
                            <p>Upcoming this month</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Documents and Pending Actions -->
                <div class="row mt-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Recent Documents</h5>
                                <p class="text-muted">Your recently uploaded and received documents</p>
                                
                                <?php if (empty($recent_docs)): ?>
                                    <p class="text-muted">No documents uploaded yet</p>
                                <?php else: ?>
                                    <?php foreach ($recent_docs as $doc): ?>
                                        <div class="doc-item">
                                            <div class="doc-icon">
                                                <?php if (strpos($doc['file_type'], 'pdf') !== false): ?>
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                <?php elseif (strpos($doc['file_type'], 'xls') !== false): ?>
                                                    <i class="bi bi-file-earmark-excel"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-file-earmark-text"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="doc-info">
                                                <h6><?php echo htmlspecialchars($doc['title']); ?></h6>
                                                <small>Uploaded on <?php echo date('d M Y', strtotime($doc['uploaded_at'])); ?></small>
                                            </div>
                                            <div class="doc-action">
                                                <a href="#" class="btn btn-sm btn-outline-secondary">View</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <div class="text-center mt-3">
                                    <a href="documents.php" class="btn btn-outline-primary">View All Documents</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Pending Actions</h5>
                                <p class="text-muted">Tasks that require your attention</p>
                                
                                <?php if ($subscription && $subscription['status'] == 'trial'): ?>
                                    <div class="task-item">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-clock text-primary me-2"></i>
                                            <div>
                                                <h6>Upgrade subscription</h6>
                                                <small>Due by <?php echo date('d M Y', strtotime($subscription['end_date'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="task-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-clock text-primary me-2"></i>
                                        <div>
                                            <h6>Upload ID proof</h6>
                                            <small>Due by <?php echo date('d M Y', strtotime('+10 days')); ?></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="task-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-clock text-primary me-2"></i>
                                        <div>
                                            <h6>Review tax declaration</h6>
                                            <small>Due by <?php echo date('d M Y', strtotime('+15 days')); ?></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="upload.php" class="btn btn-primary">
                                        <i class="bi bi-cloud-upload"></i> Upload Documents
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($subscription): ?>
                            <div class="card mt-4">
                                <div class="card-body">
                                    <h5 class="card-title">Subscription Status</h5>
                                    <?php if ($subscription['status'] == 'active'): ?>
                                        <div class="status-badge bg-success bg-opacity-10 text-success mb-2">Active</div>
                                        <p>Your subscription is active until <?php echo date('d M Y', strtotime($subscription['end_date'])); ?></p>
                                    <?php elseif ($subscription['status'] == 'trial'): ?>
                                        <div class="status-badge bg-warning bg-opacity-10 text-warning mb-2">Trial</div>
                                        <p>Your trial ends on <?php echo date('d M Y', strtotime($subscription['end_date'])); ?></p>
                                        <a href="subscription.php" class="btn btn-sm btn-primary">Upgrade Now</a>
                                    <?php else: ?>
                                        <div class="status-badge bg-danger bg-opacity-10 text-danger mb-2">Expired</div>
                                        <p>Your subscription has expired.</p>
                                        <a href="subscription.php" class="btn btn-sm btn-primary">Renew Now</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle functionality
        document.querySelector('.theme-toggle').addEventListener('click', function() {
            if (this.querySelector('i').classList.contains('bi-sun')) {
                this.querySelector('i').classList.replace('bi-sun', 'bi-moon');
                document.body.classList.add('dark-mode');
            } else {
                this.querySelector('i').classList.replace('bi-moon', 'bi-sun');
                document.body.classList.remove('dark-mode');
            }
        });
    </script>
</body>
</html> 