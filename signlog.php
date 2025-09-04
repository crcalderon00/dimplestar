<?php
session_start();

// Example login process (replace with your real DB logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';

  // Example credentials (replace with database check)
  if ($email === 'user@example.com' && $password === 'password') {
    $_SESSION['user'] = $email;
    header("Location: index.php");
    exit;
  } else {
    $error = "Invalid email or password!";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Dimple Star Transport</title>
  <link rel="stylesheet" href="style/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div id="wrapper">
  <header id="header">
    <a href="index.php"><img src="images/icon.ico" alt="Logo" class="logo"></a>
  </header>

  <main id="content">
    <h1>Login Status</h1>
    <?php if (!empty($error)): ?>
      <p style="color:red;"><?php echo $error; ?></p>
      <a href="login.php">Try Again</a>
    <?php else: ?>
      <p>Redirecting...</p>
    <?php endif; ?>
  </main>

  <footer id="footer">
    <p>&copy; <?php echo date("Y"); ?> Dimple Star Transport. All rights reserved.</p>
  </footer>
</div>
</body>
</html>
