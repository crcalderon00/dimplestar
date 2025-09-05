<?php
session_start();

// Database connection
$conn = mysqli_connect("localhost", "root", null, "dimplestar");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");

// Function to get content from database
function getContent($conn, $content_type, $default_title = '', $default_content = '') {
    $stmt = mysqli_prepare($conn, "SELECT title, content FROM site_content WHERE content_type = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $content_type);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return [
        'title' => $data['title'] ?? $default_title,
        'content' => $data['content'] ?? $default_content
    ];
}

// Get all content sections
$about_main = getContent($conn, 'about_main', 
    'About Dimple Star Transport',
    'Dimple Star Transport is one of the trusted bus companies in the Philippines, dedicated to providing safe, reliable, and affordable travel to our passengers. With decades of service, we continue to improve our fleet, facilities, and customer experience.\n\nOur mission is to make every journey comfortable and memorable, connecting people to destinations with care and efficiency.'
);

$mission = getContent($conn, 'about_mission',
    'Our Mission', 
    'To deliver safe, affordable, and reliable transportation while exceeding customer expectations.'
);

$vision = getContent($conn, 'about_vision',
    'Our Vision',
    'To be a leader in land transport services in the Philippines by continuously innovating and improving our services.'
);

$values = getContent($conn, 'about_values',
    'Our Values',
    'Safety, Reliability, Customer Care, and Commitment to Excellence.'
);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($about_main['title']); ?> - Dimple Star Transport</title>
  <link rel="stylesheet" href="style/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    .editable-content p {
      margin-bottom: 15px;
      line-height: 1.6;
    }
    .admin-edit-link {
      display: inline-block;
      margin-top: 10px;
      padding: 5px 10px;
      background: #3b82f6;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-size: 12px;
      opacity: 0.8;
      transition: opacity 0.2s;
    }
    .admin-edit-link:hover {
      opacity: 1;
    }
    .content-section {
      position: relative;
    }
  </style>
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
          <?php if($_SESSION['user_role'] === 'superadmin'): ?>
            <li><a href="admin_dashboard.php">Admin</a></li>
          <?php endif; ?>
          <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
          <li><a href="login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <!-- Main Content -->
  <main id="content">
    <div class="content-section">
      <h1><?php echo htmlspecialchars($about_main['title']); ?></h1>
      
      <section class="editable-content">
        <?php 
        // Convert line breaks to paragraphs for better display
        $content_paragraphs = explode("\n\n", $about_main['content']);
        foreach($content_paragraphs as $paragraph) {
          if(trim($paragraph)) {
            echo '<p>' . nl2br(htmlspecialchars(trim($paragraph))) . '</p>';
          }
        }
        ?>
      </section>
    </div>

    <section class="card-list">
      <div class="card content-section">
        <h2><?php echo htmlspecialchars($mission['title']); ?></h2>
        <div class="editable-content">
          <p><?php echo nl2br(htmlspecialchars($mission['content'])); ?></p>
        </div>
      </div>
      
      <div class="card content-section">
        <h2><?php echo htmlspecialchars($vision['title']); ?></h2>
        <div class="editable-content">
          <p><?php echo nl2br(htmlspecialchars($vision['content'])); ?></p>
        </div>
      </div>
      
      <div class="card content-section">
        <h2><?php echo htmlspecialchars($values['title']); ?></h2>
        <div class="editable-content">
          <p><?php echo nl2br(htmlspecialchars($values['content'])); ?></p>
        </div>
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