<?php
session_start();
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
    <nav>
      <ul id="mainnav">
        <li><a href="index.php">Home</a></li>
        <li><a href="book.php">Book</a></li>
        <li><a href="routeschedule.php">Route Schedule</a></li>
        <li><a href="terminal.php">Terminal</a></li>
        <li><a href="info.php">Info</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li class="current"><a href="login.php">Login</a></li>
      </ul>
    </nav>
  </header>

  <main id="content">
    <h1>Login</h1>
    <form action="signlog.php" method="POST" class="form">
      <label>Email</label>
      <input type="email" name="email" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <button type="submit">Login</button>
    </form>
  </main>

  <footer id="footer">
    <p>&copy; <?php echo date("Y"); ?> Dimple Star Transport. All rights reserved.</p>
  </footer>
</div>
</body>
</html>
