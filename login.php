<?php
session_start();

// Database connection
$conn = mysqli_connect("localhost", "root", null, "dimplestar");

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to prevent character set confusion attacks
mysqli_set_charset($conn, "utf8");

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login'; // 'login' or 'signup'

// Validate mode parameter
if (!in_array($mode, ['login', 'signup'])) {
    $mode = 'login';
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting - prevent brute force attacks
function checkRateLimit($action, $identifier) {
    $key = $action . '_' . $identifier;
    $current_time = time();
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [];
    }
    
    // Clean old attempts (older than 15 minutes)
    $_SESSION['rate_limit'][$key] = array_filter(
        $_SESSION['rate_limit'][$key], 
        function($timestamp) use ($current_time) {
            return ($current_time - $timestamp) < 900; // 15 minutes
        }
    );
    
    // Check if limit exceeded (5 attempts in 15 minutes)
    if (count($_SESSION['rate_limit'][$key]) >= 5) {
        return false;
    }
    
    // Add current attempt
    $_SESSION['rate_limit'][$key][] = $current_time;
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Security error. Please try again.";
    } else {
        
        if ($action === 'login') {
            // Check rate limit for login attempts
            $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (!checkRateLimit('login', $client_ip)) {
                $error = "Too many login attempts. Please try again in 15 minutes.";
            } else {
                // Login Logic with prepared statements
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                
                // Input validation
                if (empty($email) || empty($password)) {
                    $error = "Email and password are required!";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Please enter a valid email address!";
                } elseif (strlen($email) > 150) {
                    $error = "Email address is too long!";
                } else {
                    // Use prepared statement to prevent SQL injection
                    $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "s", $email);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if ($result && mysqli_num_rows($result) > 0) {
                            $user = mysqli_fetch_assoc($result);
                            
                            // Verify password
                            if (password_verify($password, $user['password'])) {
                                // Regenerate session ID to prevent session fixation
                                session_regenerate_id(true);
                                
                                $_SESSION['user'] = $user['email'];
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['user_name'] = $user['name'];
                                $_SESSION['user_role'] = $user['role'];
                                $_SESSION['login_time'] = time();
                                
                                // Clear rate limit on successful login
                                unset($_SESSION['rate_limit']['login_' . $client_ip]);
                                
                                // Redirect based on role
                                if ($user['role'] === 'superadmin') {
                                    header("Location: admin_dashboard.php");
                                } else {
                                    header("Location: index.php");
                                }
                                exit;
                            } else {
                                $error = "Invalid email or password!";
                            }
                        } else {
                            $error = "Invalid email or password!";
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $error = "System error. Please try again later.";
                    }
                }
            }
            
        } elseif ($action === 'signup') {
            // Check rate limit for signup attempts
            $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (!checkRateLimit('signup', $client_ip)) {
                $error = "Too many signup attempts. Please try again in 15 minutes.";
            } else {
                // Signup Logic with validation and prepared statements
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                // Comprehensive input validation
                if (empty($name) || empty($email) || empty($password)) {
                    $error = "All fields are required!";
                } elseif (strlen($name) < 2 || strlen($name) > 100) {
                    $error = "Name must be between 2 and 100 characters!";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Please enter a valid email address!";
                } elseif (strlen($email) > 150) {
                    $error = "Email address is too long!";
                } elseif ($password !== $confirm_password) {
                    $error = "Passwords do not match!";
                } elseif (strlen($password) < 8) {
                    $error = "Password must be at least 8 characters long!";
                } elseif (strlen($password) > 128) {
                    $error = "Password is too long (max 128 characters)!";
                } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
                    $error = "Password must contain at least one uppercase letter, one lowercase letter, and one number!";
                } elseif (preg_match('/[<>"\']/', $name . $email)) {
                    $error = "Name and email cannot contain special characters like < > \" '";
                } else {
                    // Check if email already exists using prepared statement
                    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "s", $email);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($result) > 0) {
                            $error = "Email already exists! Please use a different email.";
                        } else {
                            // Insert new user using prepared statement
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $role = 'user'; // Default role
                            
                            $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                            if ($insert_stmt) {
                                mysqli_stmt_bind_param($insert_stmt, "ssss", $name, $email, $hashed_password, $role);
                                
                                if (mysqli_stmt_execute($insert_stmt)) {
                                    $success = "Account created successfully! You can now login.";
                                    $mode = 'login'; // Switch to login mode
                                    
                                    // Clear rate limit on successful signup
                                    unset($_SESSION['rate_limit']['signup_' . $client_ip]);
                                } else {
                                    $error = "Error creating account. Please try again.";
                                }
                                mysqli_stmt_close($insert_stmt);
                            } else {
                                $error = "System error. Please try again later.";
                            }
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $error = "System error. Please try again later.";
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo ucfirst($mode); ?> - Dimple Star Transport</title>
    <link rel="stylesheet" href="style/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input {
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4a90e2;
        }
        .btn {
            padding: 12px 20px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #357abd;
        }
        .toggle-link {
            text-align: center;
            margin-top: 20px;
        }
        .toggle-link a {
            color: #4a90e2;
            text-decoration: none;
        }
        .toggle-link a:hover {
            text-decoration: underline;
        }
        .error {
            color: #e74c3c;
            background: #ffeaea;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .success {
            color: #27ae60;
            background: #eafaf1;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .auth-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
<div id="wrapper">
    <header id="header">
        <a href="index.php"><img src="images/icon.ico" alt="Logo" class="logo"></a>
    </header>

    <main id="content">
        <div class="auth-container">
            <h1 class="auth-title">
                <?php echo $mode === 'login' ? 'Welcome Back' : 'Create Account'; ?>
            </h1>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($mode === 'login'): ?>
                <!-- Login Form -->
                <form method="POST" class="auth-form">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required maxlength="150" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               autocomplete="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required 
                               maxlength="128" autocomplete="current-password">
                    </div>
                    
                    <button type="submit" class="btn">Login</button>
                </form>
                
                <div class="toggle-link">
                    <p>Don't have an account? <a href="?mode=signup">Sign up here</a></p>
                </div>

            <?php else: ?>
                <!-- Signup Form -->
                <form method="POST" class="auth-form">
                    <input type="hidden" name="action" value="signup">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required maxlength="100"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                               autocomplete="name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required maxlength="150"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               autocomplete="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required 
                               maxlength="128" autocomplete="new-password">
                        <div class="password-requirements">
                            Must be 8+ characters with uppercase, lowercase, and number
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               maxlength="128" autocomplete="new-password">
                    </div>
                    
                    <button type="submit" class="btn">Create Account</button>
                </form>
                
                <div class="toggle-link">
                    <p>Already have an account? <a href="?mode=login">Login here</a></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer id="footer">
        <p>&copy; <?php echo date("Y"); ?> Dimple Star Transport. All rights reserved.</p>
    </footer>
</div>
</body>
</html>