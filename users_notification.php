<?php
session_start();
require_once 'connect.php';

// Check if user is logged in AND has role = user
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['username'];

// Mark notifications as read when user visits this page
$update_sql = "UPDATE users_notification SET is_read = 1 WHERE user_email = ?";
$stmt_update = $conn->prepare($update_sql);
$stmt_update->bind_param("s", $user_email);
$stmt_update->execute();
$stmt_update->close();

// Fetch notifications for this user
$sql = "SELECT message, created_at, is_read FROM users_notification WHERE user_email = ? ORDER BY created_at DESC";
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
<meta charset="UTF-8" />
<title>Your Notifications</title>
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
    margin-bottom: 30px;
  }
  .notification {
    background: white;
    padding: 15px 20px;
    margin-bottom: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    border-left: 6px solid #007BFF;
  }
  .notification.unread {
    background: #e7f1ff;
    font-weight: bold;
  }
  .date {
    font-size: 0.85rem;
    color: #666;
    margin-top: 8px;
  }
  a.back {
    display: inline-block;
    margin-bottom: 20px;
    text-decoration: none;
    color: #007BFF;
  }
  a.back:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>

<header>
  <h1>Notifications for <?= htmlspecialchars($user_email) ?></h1>
</header>

<a href="user_dashboard.php" class="back">← Back to Dashboard</a>

<?php if (count($notifications) === 0): ?>
    <p>You have no notifications at this time.</p>
<?php else: ?>
    <?php foreach ($notifications as $notif): ?>
        <div class="notification <?= $notif['is_read'] ? '' : 'unread' ?>">
            <div><?= nl2br(htmlspecialchars($notif['message'])) ?></div>
            <div class="date"><?= htmlspecialchars($notif['created_at']) ?></div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
