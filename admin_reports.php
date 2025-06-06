<?php
session_start();
require_once 'connect.php';

// Check admin access
if (isset($_GET['resolve']) && is_numeric($_GET['resolve'])) {
    $resolve_id = intval($_GET['resolve']);

    // Get reporter's email
    $stmt = $conn->prepare("SELECT user_email, post_id FROM admin_reports WHERE id = ?");
    $stmt->bind_param("i", $resolve_id);
    $stmt->execute();
    $stmt->bind_result($reporter_email, $post_id);
    $stmt->fetch();
    $stmt->close();

    // Update report status
    $update = $conn->prepare("UPDATE admin_reports SET status = 'resolved' WHERE id = ?");
    $update->bind_param("i", $resolve_id);
    $update->execute();
    $update->close();

    // Notify reporter
    if ($reporter_email) {
        $msg = "Your report on post ID $post_id has been reviewed and marked as resolved.";
        $notify = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
        $notify->bind_param("ss", $reporter_email, $msg);
        $notify->execute();
        $notify->close();
    }

    header("Location: admin_reports.php");
    exit();
}


// Fetch reports with post info
$sql = "SELECT r.id, r.post_id, r.user_email AS reporter_email, r.reason, r.created_at AS report_date,
        p.title AS post_title, p.user_email AS post_owner_email
        FROM admin_reports r
        LEFT JOIN admin_post p ON r.post_id = p.id
        ORDER BY r.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin - Reports</title>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; }
  h1 { margin-bottom: 20px; }
  table { border-collapse: collapse; width: 100%; background: white; }
  th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
  th { background-color: #f2f2f2; }
  tr:hover { background-color: #f5f5f5; }
  .no-reports { color: #666; font-style: italic; }
  a { color: #007bff; text-decoration: none; }
  a:hover { text-decoration: underline; }
</style>
</head>
<body>

<h1>Reported Posts</h1>
<p><a href="admin_dashboard.php">Back to Dashboard</a></p>

<?php if ($result && $result->num_rows > 0): ?>
<table>
  <thead>
    <tr>
      <th>Report ID</th>
      <th>Post ID</th>
      <th>Post Title</th>
      <th>Post Owner</th>
      <th>Reported By</th>
      <th>Reason</th>
      <th>Report Date</th>
      <th>View Post</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['id']) ?></td>
      <td><?= htmlspecialchars($row['post_id']) ?></td>
      <td><?= htmlspecialchars($row['post_title'] ?: 'Post deleted') ?></td>
      <td><?= htmlspecialchars($row['post_owner_email'] ?: 'N/A') ?></td>
      <td><?= htmlspecialchars($row['reporter_email']) ?></td>
      <td><?= nl2br(htmlspecialchars($row['reason'])) ?></td>
      <td><?= htmlspecialchars($row['report_date']) ?></td>
<td>
  <a href="view_post.php?id=<?= urlencode($row['post_id']) ?>" target="_blank">View</a>
  | 
  <a href="admin_reports.php?resolve_id=<?= urlencode($row['id']) ?>" onclick="return confirm('Mark this report as resolved?');">Mark as Resolved</a>
</td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>
<?php else: ?>
  <p class="no-reports">No reports have been submitted yet.</p>
<?php endif; ?>

</body>
</html>
