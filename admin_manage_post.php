<?php
session_start();
require_once 'connect.php';

// Check admin session
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$message = '';

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Delete the post from admin_post
    $stmt = $conn->prepare("DELETE FROM admin_post WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "Post ID $delete_id has been deleted successfully.";
    } else {
        $message = "Error deleting post ID $delete_id.";
    }
    $stmt->close();
}

// Fetch all posts
$sql = "SELECT * FROM admin_post ORDER BY created_at DESC";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Manage Posts</title>
<style>
  body {
    font-family: Arial, sans-serif;
    padding: 20px;
    background: #f5f7fa;
  }
  h1 {
    text-align: center;
    margin-bottom: 30px;
    color: #003366;
  }
  .message {
    text-align: center;
    margin-bottom: 20px;
    font-weight: bold;
    color: green;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }
  th, td {
    padding: 12px 15px;
    border: 1px solid #ddd;
    text-align: left;
  }
  th {
    background-color: #004080;
    color: white;
  }
  tr:hover {
    background-color: #f1f9ff;
  }
  a.delete-btn {
    color: white;
    background-color: #d9534f;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.3s ease;
  }
  a.delete-btn:hover {
    background-color: #c9302c;
  }
  .back-link {
    display: inline-block;
    margin-bottom: 15px;
    font-size: 16px;
    color: #007BFF;
    text-decoration: none;
  }
  .back-link:hover {
    text-decoration: underline;
  }
</style>
<script>
  function confirmDelete(postId) {
    return confirm("Are you sure you want to delete post ID " + postId + "?");
  }
</script>
</head>
<body>

  <h1>Admin Manage All Posts</h1>

  <p><a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a></p>

  <?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($result->num_rows > 0): ?>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Type</th>
        <th>Description</th>
        <th>Location</th>
        <th>Posted By</th>
        <th>Created At</th>
        <th>Image</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($post = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($post['id']) ?></td>
          <td><?= htmlspecialchars($post['title']) ?></td>
          <td><?= htmlspecialchars($post['type']) ?></td>
          <td><?= nl2br(htmlspecialchars($post['Description'])) ?></td>
          <td><?= htmlspecialchars($post['location']) ?></td>
          <td><?= htmlspecialchars($post['user_email']) ?></td>
          <td><?= htmlspecialchars($post['created_at']) ?></td>
          <td>
            <?php if ($post['image_path'] && file_exists($post['image_path'])): ?>
              <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="Image" style="max-width:100px; max-height:80px; border-radius:6px;">
            <?php else: ?>
              No Image
            <?php endif; ?>
          </td>
          <td>
            <a href="?delete_id=<?= $post['id'] ?>" class="delete-btn" onclick="return confirmDelete(<?= $post['id'] ?>)">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p>No posts available.</p>
  <?php endif; ?>

</body>
</html>
