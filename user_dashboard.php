<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['username'];
$user_name = '';



// Fetch username from DB based on logged-in user email
$stmt = $conn->prepare("SELECT username FROM users WHERE user_email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$stmt->bind_result($user_name);
$stmt->fetch();
$stmt->close();

$unread_count = 0;

// Get unread messages count
$sql = "SELECT COUNT(*) AS unread FROM messages WHERE receiver_email = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $unread_count = $row['unread'] ?? 0;
}
$stmt->close();

// Get total posts count by user
$stmt = $conn->prepare("SELECT COUNT(*) AS total_posts FROM user_post WHERE user_email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user_posts_count = ($row = $result->fetch_assoc()) ? $row['total_posts'] : 0;
$stmt->close();

// Get total messages count received by user
$stmt = $conn->prepare("SELECT COUNT(*) AS total_messages FROM messages WHERE receiver_email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$total_messages = ($row = $result->fetch_assoc()) ? $row['total_messages'] : 0;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>User Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
<style>
    /* dashboard.css */
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: 'Poppins', sans-serif;
      background: transparent;
      overflow-x: hidden;
    }
    .background-blur {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: url('rmmc.png') no-repeat center center fixed;
      background-size: cover;
      filter: blur(14px);
      z-index: -1;
    }
    .main-wrapper {
      position: relative;
      margin: 30px auto;
      max-width: 1200px;
      padding: 30px;
      border-radius: 12px;
      color: #333;
      min-height: 100vh;
    }
    header {
      background: rgb(9, 24, 184);
      color: white;
      padding: 15px;
      border-radius: 8px;
      text-align: center;
      margin-bottom: 30px;
    }
    .stats {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 30px;
      justify-content: center;
    }
    .stat-card {
      background: rgb(255, 255, 255);
      backdrop-filter: blur(8px);
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      text-align: center;
      min-width: 180px;
      font-weight: 600;
      color: #007BFF;
    }
    .stat-card p {
      margin: 5px 0 0;
      color: #555;
      font-weight: 400;
    }
    .features {
      margin-top: 10px;
      display: grid;
      gap: 20px;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      margin-bottom: 40px;
    }
    .feature {
      background: white;
      backdrop-filter: blur(8px);
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      text-align: center;
      font-weight: 600;
      color: #007BFF;
    }
    .feature a {
      text-decoration: none;
      color: #007BFF;
      font-weight: 600;
    }
    .feature p {
      text-decoration: none;
      color: rgb(0, 0, 0);
      font-weight: 200;
    }
    .feature a:hover {
      text-decoration: underline;
    }
    .logout {
      display: block;
      margin-top: 40px;
      text-align: center;
      font-weight: 600;
    }
    .logout a {
      color: #007BFF;
      text-decoration: none;
    }
    .logout a:hover {
      text-decoration: underline;
    }
    h2 {
      color: #333;
      font-weight: 600;
      margin-top: 50px;
      margin-bottom: 20px;
      border-bottom: 2px solid #007BFF;
      padding-bottom: 5px;
    }
    .notification-badge {
      background-color: red;
      color: white;
      font-weight: bold;
      padding: 3px 7px;
      border-radius: 12px;
      font-size: 12px;
      margin-left: 6px;
      vertical-align: middle;
    }
    
</style>
</head>
<body>

<div class="background-blur"></div>

<div class="main-wrapper">
<header>
    <h1>Welcome <?= htmlspecialchars($user_name) ?>!</h1>
    <p>This is your Lost & Found user dashboard.</p>
</header>

<div class="stats">
  <div class="stat-card">
    <h3><?= $user_posts_count ?></h3>
    <p>Your Posts</p>
  </div>
  <div class="stat-card">
    <h3><?= $total_messages ?></h3>
    <p>Total Messages</p>
  </div>
  <div class="stat-card">
    <h3><?= $unread_count ?></h3>
    <p>Unread Messages</p>
  </div>
</div>

<div class="features">
  <div class="feature">
    <a href="create_post_user.php">➕ Create Lost/Found Post</a>
    <p>Report something you've lost or found.</p>
  </div>

  <div class="feature">
    <a href="view_all_posts.php">📋 View All Posts</a>
    <p>Browse all lost and found items.</p>
  </div>

  <div class="feature">
    <a href="messages.php">💬 Messages</a>
    <p>Chat with other users or the admin.</p>
  </div>

  <div class="feature">
    <a href="users_notification.php">
      🔔 Notifications
      <?php if ($unread_count > 0): ?>
        <span class="notification-badge"><?= $unread_count ?></span>
      <?php endif; ?>
    </a>
    <p>View your latest alerts and messages.</p>
  </div>

  <div class="feature">
    <a href="user_setting.php">⚙️ Settings</a>
    <p>Update your account details and preferences.</p>
  </div>
</div>

<div class="logout">
  <a href="logout.php">🔓 Logout</a>
</div>

</div>

</body>
</html>
