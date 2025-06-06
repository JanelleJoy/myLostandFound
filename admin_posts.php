<?php
session_start();
require_once 'connect.php';

// Only allow admins
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch all posts
$sql = "SELECT * FROM admin_post ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Posts</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
    th { background-color: #f4f4f4; }
    a.delete { color: red; text-decoration: none; }
    a.edit { color: blue; margin-right: 10px; text-decoration: none; }
    .message { color: green; font-weight: bold; margin-top: 10px; }
  </style>
</head>
<body>

<h1>Manage Posts</h1>
<p><a href="admin_dashboard.php">Back to Dashboard</a></p>


<?php if (isset($_SESSION['message'])): ?>
  <p class="message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Title</th>
      <th>Description</th>
      <th>Type</th>
      <th>Email</th>
      <th>Date</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo htmlspecialchars($row['title']); ?></td>
        <td><?php echo htmlspecialchars(substr($row['Description'], 0, 50)); ?>...</td>
        <td><?php echo $row['type']; ?></td>
        <td><?php echo $row['user_email']; ?></td>
        <td><?php echo $row['created_at']; ?></td>
        <td>
          <a class="edit" href="edit_post.php?id=<?php echo $row['id']; ?>">Edit</a>
          <a class="delete" href="delete_post.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

</body>
</html>
