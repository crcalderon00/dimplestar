<?php
session_start();

// Check if user is logged in and is superadmin
if (!isset($_SESSION['user']) || $_SESSION['user_role'] !== 'superadmin') {
    header("Location: login.php");
    exit;
}

// Database connection
$conn = mysqli_connect("localhost", "root", null, "dimplestar");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';
$active_page = $_GET['page'] ?? 'dashboard';

// Validate page parameter
if (!in_array($active_page, ['dashboard', 'about'])) {
    $active_page = 'dashboard';
}

// Handle content updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Security error. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_content') {
            $content_type = $_POST['content_type'] ?? '';
            $content_title = trim($_POST['content_title'] ?? '');
            $content_text = trim($_POST['content_text'] ?? '');
            
            // Validate inputs
            if (empty($content_type) || empty($content_title) || empty($content_text)) {
                $error = "All fields are required!";
            } elseif (!in_array($content_type, ['hero', 'about', 'services', 'contact'])) {
                $error = "Invalid content type!";
            } elseif (strlen($content_title) > 200) {
                $error = "Title is too long (max 200 characters)!";
            } elseif (strlen($content_text) > 5000) {
                $error = "Content is too long (max 5000 characters)!";
            } else {
                // Check if content exists
                $check_stmt = mysqli_prepare($conn, "SELECT id FROM site_content WHERE content_type = ? LIMIT 1");
                mysqli_stmt_bind_param($check_stmt, "s", $content_type);
                mysqli_stmt_execute($check_stmt);
                $result = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    // Update existing content
                    $update_stmt = mysqli_prepare($conn, "UPDATE site_content SET title = ?, content = ?, updated_at = NOW(), updated_by = ? WHERE content_type = ?");
                    mysqli_stmt_bind_param($update_stmt, "ssss", $content_title, $content_text, $_SESSION['user_id'], $content_type);
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        $message = "Content updated successfully!";
                    } else {
                        $error = "Error updating content. Please try again.";
                    }
                    mysqli_stmt_close($update_stmt);
                } else {
                    // Insert new content
                    $insert_stmt = mysqli_prepare($conn, "INSERT INTO site_content (content_type, title, content, created_by, updated_by) VALUES (?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($insert_stmt, "sssss", $content_type, $content_title, $content_text, $_SESSION['user_id'], $_SESSION['user_id']);
                    
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $message = "Content created successfully!";
                    } else {
                        $error = "Error creating content. Please try again.";
                    }
                    mysqli_stmt_close($insert_stmt);
                }
                mysqli_stmt_close($check_stmt);
            }
        }
    }
}

// Get dashboard stats
$user_count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$user_count = mysqli_fetch_assoc($user_count_result)['count'] ?? 0;

$admin_count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'superadmin'");
$admin_count = mysqli_fetch_assoc($admin_count_result)['count'] ?? 0;

// Get content for editing
$content_data = [];
$content_types = ['hero', 'about', 'services', 'contact'];
foreach ($content_types as $type) {
    $stmt = mysqli_prepare($conn, "SELECT title, content FROM site_content WHERE content_type = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $type);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $content_data[$type] = mysqli_fetch_assoc($result) ?: ['title' => '', 'content' => ''];
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Dimple Star Transport</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #334155;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: #1e293b;
            color: white;
            padding: 20px 0;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #334155;
            margin-bottom: 20px;
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            font-weight: 600;
        }
        
        .sidebar-header p {
            font-size: 14px;
            color: #94a3b8;
            margin-top: 5px;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin: 5px 0;
        }
        
        .nav-link {
            display: block;
            padding: 12px 20px;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            background: #334155;
            color: white;
            border-left-color: #3b82f6;
        }
        
        .logout-btn {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            padding: 10px;
            background: #dc2626;
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: 6px;
            font-size: 14px;
            transition: background 0.2s;
        }
        
        .logout-btn:hover {
            background: #b91c1c;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
        }
        
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 600;
            color: #3b82f6;
        }
        
        .stat-label {
            font-size: 14px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .content-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .content-form {
            padding: 20px;
        }
        
        .form-grid {
            display: grid;
            gap: 20px;
        }
        
        .content-type-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .content-type-btn {
            padding: 10px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            font-weight: 500;
        }
        
        .content-type-btn.active {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #3b82f6;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #374151;
        }
        
        .form-input, .form-textarea {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #2563eb;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        @media (max-width: 768px) {
            .admin-layout {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: relative;
            }
            
            .logout-btn {
                position: static;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                <a href="index.php" style="display: inline-block; margin-top: 10px; padding: 8px 12px; background: #10b981; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; transition: background 0.2s;">
                    🌐 View Website
                </a>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="?page=dashboard" class="nav-link <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
                        📊 Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=about" class="nav-link <?php echo $active_page === 'about' ? 'active' : ''; ?>">
                        ✏️ Manage Content
                    </a>
                </li>
            </ul>
            
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">
                    <?php 
                    echo $active_page === 'dashboard' ? 'Dashboard Overview' : 'Content Management';
                    ?>
                </h1>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($active_page === 'dashboard'): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $user_count; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $admin_count; ?></div>
                        <div class="stat-label">Administrators</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo date('Y'); ?></div>
                        <div class="stat-label">Current Year</div>
                    </div>
                </div>
                
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">System Overview</h2>
                    </div>
                    <div class="content-form">
                        <p>Welcome to the Dimple Star Transport admin dashboard. From here you can:</p>
                        <ul style="margin: 15px 0; padding-left: 20px;">
                            <li>View system statistics</li>
                            <li>Manage website content</li>
                            <li>Monitor user activity</li>
                        </ul>
                        <p>Use the navigation menu to access different management sections.</p>
                    </div>
                </div>
                
            <?php elseif ($active_page === 'about'): ?>
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Edit Website Content</h2>
                    </div>
                    <div class="content-form">
                        <form method="POST" class="form-grid">
                            <input type="hidden" name="action" value="update_content">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="content_type" id="selected_content_type" value="hero">
                            
                            <div class="content-type-selector">
                                <button type="button" class="content-type-btn active" data-type="hero">Homepage Hero</button>
                                <button type="button" class="content-type-btn" data-type="about">About Section</button>
                                <button type="button" class="content-type-btn" data-type="services">Services</button>
                                <button type="button" class="content-type-btn" data-type="contact">Contact Info</button>
                                <button type="button" class="content-type-btn" data-type="about_main">About Main Content</button>
                                <button type="button" class="content-type-btn" data-type="about_mission">About Mission</button>
                                <button type="button" class="content-type-btn" data-type="about_vision">About Vision</button>
                                <button type="button" class="content-type-btn" data-type="about_values">About Values</button>
                            </div>
                            
                            <div class="form-group">
                                <label for="content_title" class="form-label">Title</label>
                                <input type="text" id="content_title" name="content_title" class="form-input" 
                                       maxlength="200" required value="">
                            </div>
                            
                            <div class="form-group">
                                <label for="content_text" class="form-label">Content</label>
                                <textarea id="content_text" name="content_text" class="form-textarea" 
                                          maxlength="5000" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn">Update Content</button>
                        </form>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Content Change History</h2>
                    </div>
                    <div class="content-form">
                        <?php if (empty($audit_logs)): ?>
                            <p>No content changes recorded yet.</p>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                                    <thead>
                                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">Date & Time</th>
                                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">Content Section</th>
                                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">Action</th>
                                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">Admin User</th>
                                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">Title</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($audit_logs as $log): ?>
                                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                                <td style="padding: 12px; font-size: 14px;">
                                                    <?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?>
                                                </td>
                                                <td style="padding: 12px; font-weight: 500;">
                                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $log['content_type']))); ?>
                                                </td>
                                                <td style="padding: 12px;">
                                                    <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; 
                                                          background: <?php echo $log['action'] === 'CREATE' ? '#dcfce7' : '#fef3c7'; ?>; 
                                                          color: <?php echo $log['action'] === 'CREATE' ? '#166534' : '#92400e'; ?>;">
                                                        <?php echo $log['action']; ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 12px; font-weight: 500;">
                                                    <?php echo htmlspecialchars($log['user_name']); ?>
                                                </td>
                                                <td style="padding: 12px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    <?php echo htmlspecialchars($log['new_title']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        // Content type selector functionality
        const contentData = <?php echo json_encode($content_data); ?>;
        const contentButtons = document.querySelectorAll('.content-type-btn');
        const titleInput = document.getElementById('content_title');
        const textInput = document.getElementById('content_text');
        const typeInput = document.getElementById('selected_content_type');
        
        function loadContent(type) {
            const data = contentData[type] || {title: '', content: ''};
            titleInput.value = data.title || '';
            textInput.value = data.content || '';
            typeInput.value = type;
        }
        
        contentButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Update active state
                contentButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Load content
                const type = btn.dataset.type;
                loadContent(type);
            });
        });
        
        // Load initial content
        loadContent('hero');
    </script>
</body>
</html>