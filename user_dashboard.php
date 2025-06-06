<?php
session_start();
require_once 'connect.php'; // Ensure this is present

// Check if user is logged in AND has role = user
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$user_email = $_SESSION['username'];

// Initialize unread count
$unread_count = 0;

// Get count of unread notifications
$sql = "SELECT COUNT(*) AS cnt FROM users_notification WHERE user_email = ? AND is_read = 0";
$sql = "SELECT COUNT(*) AS unread FROM messages WHERE receiver_email = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
$unread_count = $row['unread'] ?? 0;
}
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Dashboard</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    padding: 30px;
  }
  header {
    background: #007BFF;
    color: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
  }
  .features {
    margin-top: 30px;
    display: grid;
    gap: 20px;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
  .feature {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    text-align: center;
  }
  .feature a {
    text-decoration: none;
    color: #007BFF;
    font-weight: bold;
  }
  .feature a:hover {
    text-decoration: underline;
  }
  .logout {
    display: block;
    margin-top: 40px;
    text-align: center;
  }
</style>
</head>
<body>

<header>
  <h1>Welcome, <?= htmlspecialchars($username) ?>!</h1>
  <p>This is your Lost & Found user dashboard.</p>
</header>

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

  <!-- Add the Settings feature here -->
  <div class="feature">
    <a href="user_setting.php">⚙️ Settings</a>
    <p>Update your account details and preferences.</p>
  </div>

</div>


<div class="logout">
  <a href="logout.php">🔓 Logout</a>
</div>

</body>
</html>
