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

// Handle file upload
$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["document"])) {
    $title = trim($_POST['document_title']);
    $target_dir = "../uploads/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_name = basename($_FILES["document"]["name"]);
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $unique_name = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $unique_name;
    
    // Allowed file types
    $allowed_types = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xls', 'xlsx');
    
    // Check if file type is allowed
    if (!in_array($file_extension, $allowed_types)) {
        $message = "Sorry, only PDF, DOC, DOCX, JPG, JPEG, PNG, XLS, XLSX files are allowed.";
        $messageType = "danger";
    } else if ($_FILES["document"]["size"] > 5000000) { // 5MB max file size
        $message = "Sorry, your file is too large. Maximum file size is 5MB.";
        $messageType = "danger";
    } else if (move_uploaded_file($_FILES["document"]["tmp_name"], $target_file)) {
        // Insert document info into database
        $stmt = $conn->prepare("INSERT INTO documents (user_id, title, file_path, file_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $title, $unique_name, $file_extension);
        
        if ($stmt->execute()) {
            $message = "The file " . htmlspecialchars($file_name) . " has been uploaded.";
            $messageType = "success";
        } else {
            $message = "Sorry, there was an error uploading your file.";
            $messageType = "danger";
        }
    } else {
        $message = "Sorry, there was an error uploading your file.";
        $messageType = "danger";
    }
}

// Get unread messages count
$stmt = $conn->prepare("SELECT COUNT(*) as msg_count FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_messages = $stmt->get_result()->fetch_assoc()['msg_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents - Tax Consultancy Portal</title>
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
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        .upload-area:hover, .upload-area.dragover {
            border-color: #0d6efd;
            background: #e9ecef;
        }
        .upload-icon {
            font-size: 48px;
            color: #adb5bd;
            margin-bottom: 15px;
        }
        .table th {
            font-weight: 600;
            color: #495057;
        }
        .theme-toggle {
            cursor: pointer;
            font-size: 1.5rem;
            color: #6c757d;
        }
        .document-type {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            font-size: 18px;
        }
        .type-pdf {
            background-color: #f8d7da;
            color: #dc3545;
        }
        .type-doc, .type-docx {
            background-color: #cfe2ff;
            color: #0d6efd;
        }
        .type-xls, .type-xlsx {
            background-color: #d1e7dd;
            color: #198754;
        }
        .type-jpg, .type-jpeg, .type-png {
            background-color: #fff3cd;
            color: #ffc107;
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
                    <a href="dashboard.php" class="sidebar-link">
                        <i class="bi bi-grid"></i> Dashboard
                    </a>
                    <a href="documents.php" class="sidebar-link">
                        <i class="bi bi-file-earmark-text"></i> View Documents
                    </a>
                    <a href="upload.php" class="sidebar-link active">
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
                    <h1 class="h2">Upload Documents</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="theme-toggle">
                            <i class="bi bi-sun"></i>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Upload New Document</h5>
                                
                                <form method="POST" enctype="multipart/form-data" id="upload-form">
                                    <div class="mb-3">
                                        <label for="document_title" class="form-label">Document Title</label>
                                        <input type="text" class="form-control" id="document_title" name="document_title" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Document File</label>
                                        <div class="upload-area" id="upload-area">
                                            <i class="bi bi-cloud-arrow-up upload-icon"></i>
                                            <h5>Drag & Drop files here</h5>
                                            <p class="text-muted">or click to browse files</p>
                                            <input type="file" name="document" id="file-input" class="d-none" required>
                                            <div class="file-info mt-3 d-none">
                                                <p class="mb-1"><span id="file-name"></span> (<span id="file-size"></span>)</p>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">Upload Document</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Recommended Documents</h5>
                                <div class="list-group">
                                    <div class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                                        <div class="document-type type-pdf">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Income Tax Return</h6>
                                            <p class="text-muted mb-0 small">Upload your latest ITR form</p>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary ms-auto">Upload</button>
                                    </div>
                                    <div class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                                        <div class="document-type type-doc">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">ID Proof</h6>
                                            <p class="text-muted mb-0 small">PAN card, Aadhar or passport</p>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary ms-auto">Upload</button>
                                    </div>
                                    <div class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                                        <div class="document-type type-xls">
                                            <i class="bi bi-file-earmark-excel"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Investment Statements</h6>
                                            <p class="text-muted mb-0 small">Bank statements for tax deductions</p>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary ms-auto">Upload</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Tips for Uploading</h5>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-light rounded-circle p-2 me-3">
                                        <i class="bi bi-file-check text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Accepted File Formats</h6>
                                        <p class="text-muted mb-0 small">PDF, DOC, DOCX, JPG, JPEG, PNG, XLS, XLSX</p>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-light rounded-circle p-2 me-3">
                                        <i class="bi bi-file-earmark-text text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Maximum File Size</h6>
                                        <p class="text-muted mb-0 small">5MB per document</p>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-light rounded-circle p-2 me-3">
                                        <i class="bi bi-shield-lock text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Secure Storage</h6>
                                        <p class="text-muted mb-0 small">All documents are encrypted and securely stored</p>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle p-2 me-3">
                                        <i class="bi bi-eye text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Privacy</h6>
                                        <p class="text-muted mb-0 small">Only you and authorized tax experts can view your documents</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload handling
        const uploadArea = document.getElementById('upload-area');
        const fileInput = document.getElementById('file-input');
        const fileName = document.getElementById('file-name');
        const fileSize = document.getElementById('file-size');
        const fileInfo = document.querySelector('.file-info');
        const progressBar = document.querySelector('.progress-bar');

        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateFileInfo(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                updateFileInfo(fileInput.files[0]);
            }
        });

        function updateFileInfo(file) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.classList.remove('d-none');
            
            // Simulate upload progress (just for visual effect)
            let width = 0;
            const interval = setInterval(() => {
                width += 5;
                progressBar.style.width = width + '%';
                
                if (width >= 100) {
                    clearInterval(interval);
                }
            }, 50);
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

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