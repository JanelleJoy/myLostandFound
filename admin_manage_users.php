<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Search filter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$users = [];

if ($searchTerm !== '') {
    $searchTermLike = "%$searchTerm%";
    $stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users WHERE name LIKE ? OR email LIKE ?");
    $stmt->bind_param("ss", $searchTermLike, $searchTermLike);
} else {
    $stmt = $conn->prepare("SELECT id, name, user_email, role, created_at FROM users");
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users</title>
   <p><a href="admin_dashboard.php">Back to Dashboard</a></p>
<style>
  body { font-family: Arial; background: #f4f4f4; padding: 20px; }
  h2 { color: #333; }
  .search-box { margin-bottom: 20px; }
  table { width: 100%; border-collapse: collapse; background: white; }
  th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
  th { background: #333; color: white; }
  a.button { padding: 5px 10px; text-decoration: none; background: #007BFF; color: white; border-radius: 4px; }
  a.button:hover { background: #0056b3; }
</style>
</head>
<body>
<h2>Manage Users</h2>

<form method="GET" class="search-box">
  <input type="text" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($searchTerm) ?>">
  <button type="submit">Search</button>
</form>

<table>
  <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>Role</th>
    <th>Created At</th>
    <th>Actions</th>
  </tr>
  <?php if (count($users) > 0): ?>
    <?php foreach ($users as $user): ?>
      <tr>
        <td><?= $user['id'] ?></td>
        <td><?= htmlspecialchars($user['name']) ?></td>
        <td><?= htmlspecialchars($user['user_email']) ?></td>
        <td><?= htmlspecialchars($user['role']) ?></td>
        <td><?= htmlspecialchars($user['created_at']) ?></td>
        <td>
          <a class="button" href="edit_user.php?id=<?= $user['id'] ?>">Edit</a>
          <a class="button" href="delete_user.php?id=<?= $user['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr><td colspan="6">No users found.</td></tr>
  <?php endif; ?>
</table>
</body>
</html>
