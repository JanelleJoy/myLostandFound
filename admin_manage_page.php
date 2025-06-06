<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'connect.php';

// Redirect if not admin
if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['user_email'];
$user_name = $_SESSION['user_name'] ?? 'Admin';

// Handle mark as claimed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'], $_POST['new_status'])) {
    $item_id = intval($_POST['item_id']);
    $new_status = $_POST['new_status'] === 'claimed' ? 'claimed' : 'pending';

    $stmt = $conn->prepare("UPDATE admin_post SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $item_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Fetch user email and send notification (simulated)
        if ($new_status === 'claimed') {
            $stmt2 = $conn->prepare("SELECT user_email, title FROM admin_post WHERE id = ?");
            $stmt2->bind_param("i", $item_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($item = $result2->fetch_assoc()) {
                $to = $item['user_email'];
                $subject = "Lost & Found Item Claimed: " . $item['title'];
                $message = "Hello,\n\nYour item '{$item['title']}' has been marked as claimed.\n\nThank you.";
                $headers = "From: no-reply@lostandfound.com";

                // mail($to, $subject, $message, $headers); // Uncomment when mail server is configured
            }
        }
        header("Location: admin_manage_page.php?updated=1");
        exit();
    } else {
        $error_msg = "Update failed or no changes made.";
    }
}

// Fetch all posts
$sql = "SELECT * FROM admin_post ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Manage - Lost and Found</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      margin: 0;
      padding: 0;
    }
    .topnav {
      background-color: #333;
      padding: 14px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: white;
      flex-wrap: wrap;
    }
    .topnav .nav-left a {
      color: white;
      margin-right: 15px;
      text-decoration: none;
      font-weight: bold;
    }
    .topnav .nav-left a:hover {
      text-decoration: underline;
    }
    .topnav .nav-right {
      font-size: 14px;
      font-weight: bold;
    }
    .container {
      max-width: 1100px;
      margin: 30px auto;
      background: white;
      padding: 20px;
      border-radius: 10px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }
    tr:hover {
      background-color: #f1f1f1;
    }
    .status-pending {
      color: orange;
      font-weight: bold;
    }
    .status-claimed {
      color: green;
      font-weight: bold;
    }
    button {
      padding: 6px 12px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background-color: #218838;
    }
    .success {
      color: green;
      font-weight: bold;
    }
    .error {
      color: red;
      font-weight: bold;
    }
    small {
      color: #555;
    }
  </style>
</head>
<body>
  <div class="topnav">
    <div class="nav-left">
      <a href="admin_dashboard.php">Dashboard</a>
      <a href="admin_manage_page.php">Manage Items</a>
      <a href="admin_users.php">Manage Users</a>
      <a href="admin_chats.php">Chat</a>
      <a href="admin_posts.php">Post Item</a>
      <a href="logout.php">Logout</a>
    </div>
    <div class="nav-right">
      Welcome, <?= htmlspecialchars($user_name) ?> (Admin)
    </div>
  </div>

  <div class="container">
    <h2>Manage Lost & Found Items</h2>

    <?php if (!empty($error_msg)): ?>
      <p class="error"><?= htmlspecialchars($error_msg) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
      <p class="success">Item status updated successfully!</p>
    <?php endif; ?>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>User Email</th>
          <th>Type</th>
          <th>Title</th>
          <th>Description</th>
          <th>Location</th>
          <th>Date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows === 0): ?>
          <tr><td colspan="9">No items found.</td></tr>
        <?php else: ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['user_email'] ?? '') ?></td>
              <td><?= ucfirst(htmlspecialchars($row['type'] ?? '')) ?></td>
              <td><?= htmlspecialchars($row['title'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['location'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['date'] ?? '') ?></td>
              <td class="status-<?= htmlspecialchars($row['status'] ?? '') ?>">
                <?= ucfirst(htmlspecialchars($row['status'] ?? '')) ?>
              </td>
              <td>
                <?php if (($row['status'] ?? '') !== 'claimed'): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="new_status" value="claimed">
                    <button type="submit">Mark as Claimed</button>
                  </form>
                <?php else: ?>
                  ✔️ Claimed<br>
                  <small>Notification sent to <strong><?= htmlspecialchars($row['user_email'] ?? 'user') ?></strong></small>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
