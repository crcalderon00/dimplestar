<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About Us - Dimple Star Transport</title>
  <link rel="stylesheet" href="style/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div id="wrapper">
  <!-- Header -->
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
        <li class="current"><a href="about.php">About</a></li>
        <?php if(isset($_SESSION['user'])): ?>
          <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
          <li><a href="login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <!-- Main Content -->
  <main id="content">
    <h1>About Dimple Star Transport</h1>
    <section>
      <p>
        Dimple Star Transport is one of the trusted bus companies in the Philippines, 
        dedicated to providing safe, reliable, and affordable travel to our passengers. 
        With decades of service, we continue to improve our fleet, facilities, and customer experience.
      </p>
      <p>
        Our mission is to make every journey comfortable and memorable, connecting people to destinations with care and efficiency.
      </p>
    </section>

    <section class="card-list">
      <div class="card">
        <h2>Our Mission</h2>
        <p>To deliver safe, affordable, and reliable transportation while exceeding customer expectations.</p>
      </div>
      <div class="card">
        <h2>Our Vision</h2>
        <p>To be a leader in land transport services in the Philippines by continuously innovating and improving our services.</p>
      </div>
      <div class="card">
        <h2>Our Values</h2>
        <p>Safety, Reliability, Customer Care, and Commitment to Excellence.</p>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer id="footer">
    <p>&copy; <?php echo date("Y"); ?> Dimple Star Transport. All rights reserved.</p>
  </footer>
</div>
</body>
</html>
