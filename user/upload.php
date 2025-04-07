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

// Get unread message count
$stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE recipient_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$unread_messages = $result->fetch_assoc()['unread_count'];
$stmt->close();

// Handle file upload
$upload_error = '';
$upload_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    // Define allowed file types and max size (5MB)
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                     'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                     'image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 5 * 1024 * 1024; // 5MB in bytes
    
    $file = $_FILES['document'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $doc_type = mysqli_real_escape_string($conn, $_POST['doc_type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_error = 'An error occurred during file upload. Please try again.';
    } elseif (!in_array($file['type'], $allowed_types)) {
        $upload_error = 'Invalid file type. Allowed types: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG.';
    } elseif ($file['size'] > $max_size) {
        $upload_error = 'File size exceeds the limit of 5MB.';
    } elseif (empty($title)) {
        $upload_error = 'Please provide a document title.';
    } else {
        // Create uploads directory if it doesn't exist
        $upload_dir = "../uploads/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid() . '_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $unique_filename;
        
        // Move file to uploads directory
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Insert document info into database
            $stmt = $conn->prepare("INSERT INTO documents (user_id, title, file_name, file_type, file_size, file_path, document_type, description, uploaded_by, upload_date) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issidssi", $user_id, $title, $file['name'], $file['type'], $file['size'], $unique_filename, $doc_type, $description, $user_id);
            
            if ($stmt->execute()) {
                $upload_success = 'Document uploaded successfully!';
            } else {
                $upload_error = 'Database error. Please try again later.';
                // Remove uploaded file if database insertion fails
                unlink($upload_path);
            }
            $stmt->close();
        } else {
            $upload_error = 'Failed to move uploaded file. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents - Tax Consultancy Portal</title>
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
        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
        }
        .card-body {
            padding: 1.5rem;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            background-color: var(--light-bg);
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: var(--primary-color);
            background-color: var(--primary-light);
        }
        .upload-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .form-label {
            font-weight: 500;
            color: #4a5568;
        }
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 98, 255, 0.1);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-outline-secondary {
            color: #4a5568;
            border-color: #e2e8f0;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
        }
        .recommended-document-item {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            background-color: var(--light-bg);
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        .recommended-document-item:hover {
            background-color: var(--primary-light);
        }
        .doc-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        .tip-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        .tip-icon {
            margin-right: 0.75rem;
            color: var(--primary-color);
            font-size: 1.25rem;
            line-height: 1.5;
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-grid-1x2-fill"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="upload.php">
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
                <h1 class="page-heading">Upload Documents</h1>

                <?php if (!empty($upload_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $upload_error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($upload_success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $upload_success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Upload Area -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                Upload New Document
                            </div>
                            <div class="card-body">
                                <form action="upload.php" method="POST" enctype="multipart/form-data">
                                    <div class="upload-area mb-4" id="dropArea">
                                        <div class="upload-icon">
                                            <i class="bi bi-cloud-arrow-up"></i>
                                        </div>
                                        <h5 class="mb-2">Drag and drop files here</h5>
                                        <p class="text-muted mb-4">or click to browse your files</p>
                                        <input type="file" id="file-upload" name="document" class="d-none">
                                        <button type="button" class="btn btn-primary" id="browseBtn">
                                            <i class="bi bi-file-earmark-plus me-2"></i>Choose File
                                        </button>
                                    </div>
                                    <div id="selected-file" class="alert alert-info d-none mb-4">
                                        <i class="bi bi-file-earmark me-2"></i>
                                        <span id="file-name">No file selected</span>
                                    </div>

                                    <div class="mb-3">
                                        <label for="title" class="form-label">Document Title</label>
                                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Income Tax Return 2022-23" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="doc_type" class="form-label">Document Type</label>
                                        <select class="form-select" id="doc_type" name="doc_type" required>
                                            <option value="" selected disabled>Select document type</option>
                                            <option value="tax_return">Tax Return</option>
                                            <option value="form16">Form 16</option>
                                            <option value="investment_proof">Investment Proof</option>
                                            <option value="property_tax">Property Tax Receipt</option>
                                            <option value="rent_receipt">Rent Receipt</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label for="description" class="form-label">Description (Optional)</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Add any additional details about this document"></textarea>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-outline-secondary me-2" onclick="window.location.href='dashboard.php'">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Upload Document</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Recommended Documents and Tips -->
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                Recommended Documents
                            </div>
                            <div class="card-body">
                                <div class="recommended-document-item">
                                    <div class="doc-icon bg-danger text-white">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Income Tax Return</h6>
                                        <small class="text-muted">Current financial year</small>
                                    </div>
                                </div>
                                <div class="recommended-document-item">
                                    <div class="doc-icon bg-primary text-white">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Form 16</h6>
                                        <small class="text-muted">From employer</small>
                                    </div>
                                </div>
                                <div class="recommended-document-item">
                                    <div class="doc-icon bg-success text-white">
                                        <i class="bi bi-file-earmark-spreadsheet"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Investment Declarations</h6>
                                        <small class="text-muted">For tax exemptions</small>
                                    </div>
                                </div>
                                <div class="recommended-document-item">
                                    <div class="doc-icon bg-warning text-white">
                                        <i class="bi bi-file-earmark-image"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Property Tax Receipt</h6>
                                        <small class="text-muted">If applicable</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                Tips for Uploading
                            </div>
                            <div class="card-body">
                                <div class="tip-item">
                                    <div class="tip-icon">
                                        <i class="bi bi-check-circle-fill"></i>
                                    </div>
                                    <div>
                                        <strong>Accepted File Formats</strong>
                                        <p class="text-muted mb-0">PDF, DOC, DOCX, JPG, JPEG, PNG, XLS, XLSX</p>
                                    </div>
                                </div>
                                <div class="tip-item">
                                    <div class="tip-icon">
                                        <i class="bi bi-check-circle-fill"></i>
                                    </div>
                                    <div>
                                        <strong>Maximum File Size</strong>
                                        <p class="text-muted mb-0">5MB per file</p>
                                    </div>
                                </div>
                                <div class="tip-item">
                                    <div class="tip-icon">
                                        <i class="bi bi-shield-lock-fill"></i>
                                    </div>
                                    <div>
                                        <strong>Secure Storage</strong>
                                        <p class="text-muted mb-0">All documents are encrypted and stored securely</p>
                                    </div>
                                </div>
                                <div class="tip-item">
                                    <div class="tip-icon">
                                        <i class="bi bi-eye-slash-fill"></i>
                                    </div>
                                    <div>
                                        <strong>Privacy</strong>
                                        <p class="text-muted mb-0">Your documents are only accessible to you and your assigned tax consultant</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropArea = document.getElementById('dropArea');
            const fileInput = document.getElementById('file-upload');
            const browseBtn = document.getElementById('browseBtn');
            const selectedFile = document.getElementById('selected-file');
            const fileName = document.getElementById('file-name');
            
            // Trigger file input when clicking the browse button or drop area
            browseBtn.addEventListener('click', function() {
                fileInput.click();
            });
            
            dropArea.addEventListener('click', function(event) {
                if (event.target !== browseBtn && !browseBtn.contains(event.target)) {
                    fileInput.click();
                }
            });
            
            // Highlight drop area when dragging files over it
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    dropArea.classList.add('bg-light');
                }, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    dropArea.classList.remove('bg-light');
                }, false);
            });
            
            // Handle dropped files
            dropArea.addEventListener('drop', function(e) {
                e.preventDefault();
                fileInput.files = e.dataTransfer.files;
                updateFileInfo();
            });
            
            // Handle selected files
            fileInput.addEventListener('change', updateFileInfo);
            
            function updateFileInfo() {
                if (fileInput.files.length > 0) {
                    fileName.textContent = fileInput.files[0].name;
                    selectedFile.classList.remove('d-none');
                } else {
                    selectedFile.classList.add('d-none');
                }
            }
        });
    </script>
</body>
</html> 