<?php
session_start();
require_once 'connect.php';

// Check admin login
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Mark item as claimed if requested
if (isset($_GET['claim'])) {
    $post_id = intval($_GET['claim']);
    $stmt = $conn->prepare("UPDATE admin_post SET claimed = 1 WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Post #$post_id marked as claimed.";
    } else {
        $_SESSION['message'] = "Failed to mark post #$post_id as claimed.";
    }
    $stmt->close();
    header("Location: manage_posts.php");
    exit();
}

// Fetch all posts ordered by newest first
$result = $conn->query("SELECT * FROM admin_post ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Manage Posts - Admin</title>
<style>
  /* Same styling as before or your own */
  body { font-family: Arial, sans-serif; padding: 30px; background: #f0f0f0; }
  table { width: 100%; border-collapse: collapse; background: white; }
  th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
  th { background: #007bff; color: white; }
  a { color: #007bff; text-decoration: none; }
  a:hover { text-decoration: underline; }
  .message { color: green; font-weight: bold; margin-bottom: 20px; }
</style>
</head>
<body>

<h1>Manage Posts</h1>
<a href="admin_dashboard.php">← Back to Dashboard</a>

<?php if (isset($_SESSION['message'])): ?>
    <p class="message"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Title</th>
      <th>Description</th>
      <th>User Email</th>
      <th>Date Created</th>
      <th>Claimed</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?= $row['id'] ?></td>
      <td><?= htmlspecialchars($row['title']) ?></td>
      <td><?= htmlspecialchars(substr($row['Description'], 0, 50)) ?>...</td>
      <td><?= htmlspecialchars($row['user_email']) ?></td>
      <td><?= $row['created_at'] ?></td>
      <td>
        <?php if ($row['claimed']): ?>
          <strong style="color: green;">Claimed</strong>
        <?php else: ?>
          <a href="manage_posts.php?claim=<?= $row['id'] ?>" onclick="return confirm('Mark this item as claimed?')">Mark Claimed</a>
        <?php endif; ?>
      </td>
      <td>
        <a href="edit_post.php?id=<?= $row['id'] ?>">Edit</a> |
        <a href="delete_post.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
      </td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>

</body>
</html>
