<?php
session_start();
require_once 'connect.php';

// Only allow access for admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claim_id = $_POST['claim_id'] ?? null;
    $new_status = $_POST['status'] ?? null;

    if ($claim_id && in_array($new_status, ['approved', 'rejected'])) {
        $update = $conn->prepare("UPDATE claims SET status = ? WHERE id = ?");
        $update->bind_param("si", $new_status, $claim_id);
        $update->execute();
        $update->close();
    }
    header("Location: admin_post.php");
    exit();
}

// Fetch all claims + post info
$sql = "SELECT 
            c.id AS claim_id, c.status, c.user_email, c.receipt_code, 
            p.title, p.type, p.location, p.description, p.image_path, p.created_at 
        FROM claims c
        JOIN admin_post p ON c.post_id = p.id
        WHERE c.post_type = 'admin'
        ORDER BY c.status ASC, p.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Manage Claims</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f8f8f8;
    padding: 20px;
  }

  h1 {
    text-align: center;
    color: #007BFF;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
    background-color: #fff;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }

  th, td {
    padding: 14px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    vertical-align: top;
  }

  th {
    background-color: #007BFF;
    color: white;
  }

  img {
    max-width: 120px;
    height: auto;
    border: 1px solid #ccc;
    border-radius: 4px;
  }

  .actions form {
    display: flex;
    gap: 10px;
  }

  .actions select, .actions button {
    padding: 6px;
  }

  .back-link {
    margin-bottom: 15px;
    display: block;
  }
</style>
</head>
<body>

<h1>Admin - Manage Claims</h1>
<a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>

<table>
  <thead>
    <tr>
      <th>Image</th>
      <th>Title</th>
      <th>Description</th>
      <th>Type</th>
      <th>Location</th>
      <th>Posted On</th>
      <th>User Email</th>
      <th>Receipt Code</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($result->num_rows === 0): ?>
      <tr><td colspan="10" style="text-align:center;">No claims found.</td></tr>
    <?php else: ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td>
            <?php if (!empty($row['image_path'])): ?>
              <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Item Image">
            <?php else: ?>
              No image
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td><?= htmlspecialchars($row['description']) ?></td>
          <td><?= htmlspecialchars($row['type']) ?></td>
          <td><?= htmlspecialchars($row['location']) ?></td>
          <td><?= htmlspecialchars($row['created_at']) ?></td>
          <td><?= htmlspecialchars($row['user_email']) ?></td>
          <td><strong><?= htmlspecialchars($row['receipt_code']) ?></strong></td>
          <td><strong><?= ucfirst(htmlspecialchars($row['status'])) ?></strong></td>
          <td class="actions">
            <?php if ($row['status'] === 'pending'): ?>
              <form method="post">
                <input type="hidden" name="claim_id" value="<?= $row['claim_id'] ?>">
                <select name="status" required>
                  <option value="">-- Select --</option>
                  <option value="approved">Approve</option>
                  <option value="rejected">Reject</option>
                </select>
                <button type="submit">Update</button>
              </form>
            <?php else: ?>
              <em>No action</em>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php endif; ?>
  </tbody>
</table>

</body>
</html>
