<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require_once '../includes/config.php';

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get document count
$stmt = $conn->prepare("SELECT COUNT(*) AS doc_count FROM documents WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$doc_count = $result->fetch_assoc()['doc_count'];
$stmt->close();

// Get documents uploaded this month
$current_month = date('m');
$current_year = date('Y');
$stmt = $conn->prepare("SELECT COUNT(*) AS monthly_count FROM documents WHERE user_id = ? AND MONTH(upload_date) = ? AND YEAR(upload_date) = ?");
$stmt->bind_param("iii", $user_id, $current_month, $current_year);
$stmt->execute();
$result = $stmt->get_result();
$monthly_count = $result->fetch_assoc()['monthly_count'];
$stmt->close();

// Get recent documents (limit to 3)
$stmt = $conn->prepare("SELECT d.*, u.first_name, u.last_name FROM documents d 
                        LEFT JOIN users u ON d.uploaded_by = u.id 
                        WHERE d.user_id = ? 
                        ORDER BY d.upload_date DESC LIMIT 3");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_docs = $stmt->get_result();
$stmt->close();

// Get unread message count
$stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE recipient_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$unread_messages = $result->fetch_assoc()['unread_count'];
$stmt->close();

// Function to get initials from name
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials;
}

// Get user's full name and initials
$full_name = $user['first_name'] . ' ' . $user['last_name'];
$initials = getInitials($full_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Tax Consultancy Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0062ff;
            --primary-light: #e6f0ff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --dark-text: #343a40;
            --border-color: #e9ecef;
        }
        body {
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f5f9ff;
            color: #333;
        }
        .sidebar {
            background-color: white;
            color: var(--dark-text);
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            height: 100vh;
            position: fixed;
            z-index: 100;
            padding: 0;
            border-right: 1px solid var(--border-color);
        }
        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        .sidebar .nav-link {
            color: #718096;
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            margin: 0.25rem 0;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-link:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        .sidebar .nav-link.active {
            color: var(--primary-color);
            background-color: var(--primary-light);
            border-left: 3px solid var(--primary-color);
        }
        .sidebar .nav-link i {
            width: 24px;
            font-size: 1.25rem;
            margin-right: 10px;
            color: #718096;
        }
        .sidebar .nav-link.active i,
        .sidebar .nav-link:hover i {
            color: var(--primary-color);
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .page-heading {
            margin-bottom: 2rem;
            font-weight: 600;
            font-size: 2rem;
        }
        .stat-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
        }
        .stat-card h3 {
            margin-bottom: 0.5rem;
            color: #4a5568;
            font-size: 1rem;
            font-weight: 500;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .document-meta {
            color: #718096;
            font-size: 0.875rem;
        }
        .document-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }
        .document-item:last-child {
            border-bottom: none;
        }
        .document-icon {
            width: 40px;
            margin-right: 1rem;
            color: #4a5568;
        }
        .document-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        .badge-notification {
            position: absolute;
            top: -5px;
            right: -5px;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .unread-badge {
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.5rem;
        }
        .profile-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 1rem;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 sidebar">
                <div class="sidebar-brand">
                    <h4 class="mb-0">Ut Corporate Services</h4>
                    <p class="text-muted mb-0">Client Portal</p>
                </div>
                <ul class="nav flex-column mt-3">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-grid-1x2-fill"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="upload.php">
                            <i class="bi bi-upload"></i> Upload Documents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="documents.php">
                            <i class="bi bi-file-earmark-text"></i> View Documents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">
                            <i class="bi bi-chat-left-text"></i> Messages 
                            <?php if ($unread_messages > 0): ?>
                                <span class="unread-badge"><?php echo $unread_messages; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="bi bi-calendar"></i> Schedule Appointment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-left"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 main-content">
                <h1 class="page-heading">Dashboard</h1>

                <!-- Stats Card -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <h3>Documents</h3>
                            <div class="stat-value"><?php echo $doc_count; ?></div>
                            <div class="document-meta"><?php echo $monthly_count; ?> uploaded this month</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Documents -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="stat-card">
                            <h3 class="mb-4">Recent Documents</h3>
                            <p class="text-muted">Your recently uploaded documents</p>
                            
                            <?php if ($recent_docs->num_rows > 0): ?>
                                <?php while ($doc = $recent_docs->fetch_assoc()): ?>
                                    <div class="document-item">
                                        <div class="document-icon">
                                            <?php 
                                            $file_ext = pathinfo($doc['file_name'], PATHINFO_EXTENSION);
                                            $icon_class = 'bi-file-earmark-text';
                                            
                                            if (in_array($file_ext, ['pdf'])) {
                                                $icon_class = 'bi-file-earmark-pdf';
                                            } elseif (in_array($file_ext, ['doc', 'docx'])) {
                                                $icon_class = 'bi-file-earmark-word';
                                            } elseif (in_array($file_ext, ['xls', 'xlsx'])) {
                                                $icon_class = 'bi-file-earmark-excel';
                                            } elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                                $icon_class = 'bi-file-earmark-image';
                                            }
                                            ?>
                                            <i class="bi <?php echo $icon_class; ?> fs-2"></i>
                                        </div>
                                        <div>
                                            <div class="document-title"><?php echo htmlspecialchars($doc['title']); ?></div>
                                            <?php if ($doc['uploaded_by'] == $user_id): ?>
                                                <div class="document-meta">Uploaded by You</div>
                                            <?php else: ?>
                                                <div class="document-meta">Uploaded by <?php echo htmlspecialchars($doc['first_name']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p>You haven't uploaded any documents yet.</p>
                                    <a href="upload.php" class="btn btn-primary">Upload Your First Document</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 