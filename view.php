<?php
session_start();

// Example: Fetch bookings (replace with DB queries)
$bookings = [
  ["id" => 1, "name" => "Juan Dela Cruz", "route" => "Manila - Baguio", "date" => "2025-09-10", "seats" => 2],
  ["id" => 2, "name" => "Maria Santos", "route" => "Baguio - Manila", "date" => "2025-09-12", "seats" => 1],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Bookings - Dimple Star Transport</title>
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
        <li><a href="about.php">About</a></li>
        <?php if(isset($_SESSION['user'])): ?>
          <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
          <li><a href="login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main id="content">
    <h1>View Bookings</h1>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Route</th>
            <th>Date</th>
            <th>Seats</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?php echo $b['id']; ?></td>
            <td><?php echo $b['name']; ?></td>
            <td><?php echo $b['route']; ?></td>
            <td><?php echo $b['date']; ?></td>
            <td><?php echo $b['seats']; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

  <footer id="footer">
    <p>&copy; <?php echo date("Y"); ?> Dimple Star Transport. All rights reserved.</p>
  </footer>
</div>
</body>
</html>
