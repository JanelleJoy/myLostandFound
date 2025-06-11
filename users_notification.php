<?php
session_start();
require_once 'connect.php';

// Check login & role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['username'];

// Mark notifications as read
$update_sql = "UPDATE users_notification SET is_read = 1 WHERE user_email = ?";
$stmt_update = $conn->prepare($update_sql);
$stmt_update->bind_param("s", $user_email);
$stmt_update->execute();
$stmt_update->close();

// Fetch notifications
$sql = "SELECT message, created_at FROM users_notification WHERE user_email = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Notifications</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: transparent;
    }

    .background-blur {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: url('rmmc.png') no-repeat center center fixed;
      background-size: cover;
      filter: blur(14px);
      z-index: -1;
    }

    .container {
      max-width: 800px;
      margin: 50px auto;
      padding: 30px;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 14px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    header {
      text-align: center;
      padding: 20px;
      background: rgb(5, 14, 185);
      color: white;
      border-radius: 10px;
      margin-bottom: 30px;
      font-size: 1.5rem;
    }

    .notifications {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .notification {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    .notification time {
      font-size: 0.85rem;
      color: #777;
      display: block;
      margin-top: 10px;
    }

    .no-data {
      text-align: center;
      font-size: 1rem;
      color: #666;
    }

    a.back-link {
      display: inline-block;
      margin-top: 20px;
      text-decoration: none;
      font-weight: 700;
      color: #333;
    }

    a.back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="background-blur"></div>

<div class="container">
  <header>
    Notifications for <?= htmlspecialchars($user_email) ?>
  </header>

  <a href="user_dashboard.php" class="back-link">← Back to Dashboard</a>

  <?php if (count($notifications) === 0): ?>
    <p class="no-data">You have no notifications at this time.</p>
  <?php else: ?>
    <div class="notifications">
      <?php foreach ($notifications as $notif): ?>
        <div class="notification">
          <div><?= nl2br(htmlspecialchars($notif['message'])) ?></div>
          <time><?= date("F j, Y, g:i A", strtotime($notif['created_at'])) ?></time>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
