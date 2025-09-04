<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Our Terminals - Dimple Star Transport</title>
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
        <li class="current"><a href="terminal.php">Terminal</a></li>
        <li><a href="info.php">Info</a></li>
        <li><a href="contact.php">Contact</a></li>
        <?php if(isset($_SESSION['user'])): ?>
          <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
          <li><a href="login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main id="content">
    <h1>Our Terminals</h1>
    <div class="card-list">
      <div class="card">
        <h2>Manila Terminal</h2>
        <p>Cubao, Quezon City</p>
      </div>
      <div class="card">
        <h2>Baguio Terminal</h2>
        <p>Governor Pack Road</p>
      </div>
    </div>
  </main>

  <footer id="footer">
    <p>&copy; <?php echo date("Y"); ?> Dimple Star Transport. All rights reserved.</p>
  </footer>
</div>
</body>
</html>
