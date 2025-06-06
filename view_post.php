<?php
session_start();
require_once 'connect.php';

// Check admin access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Validate and get post ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid post ID.");
}

$post_id = (int)$_GET['id'];

// Handle form submissions for resolve or delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Get reporters emails to notify
    $sql_reporters = "SELECT DISTINCT user_email FROM admin_reports WHERE post_id = ?";
    $stmt_reporters = $conn->prepare($sql_reporters);
    $stmt_reporters->bind_param("i", $post_id);
    $stmt_reporters->execute();
    $result_reporters = $stmt_reporters->get_result();

    $reporters_emails = [];
    while ($row = $result_reporters->fetch_assoc()) {
        $reporters_emails[] = $row['user_email'];
    }
    $stmt_reporters->close();

    if ($action === 'delete') {
        // Delete the post
        $sql_delete = "DELETE FROM admin_post WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $post_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // Notify reporters
        $msg = "The post you reported (ID: $post_id) has been deleted by the admin.";
        foreach ($reporters_emails as $email) {
            $stmt_notif = $conn->prepare("INSERT INTO user_notifications (user_email, message) VALUES (?, ?)");
            $stmt_notif->bind_param("ss", $email, $msg);
            $stmt_notif->execute();
            $stmt_notif->close();
        }

        header("Location: admin_reports.php?msg=Post+deleted+and+reporters+notified");
        exit();
    } elseif ($action === 'resolve') {
        // Mark all reports for this post as resolved
        $sql_resolve = "UPDATE admin_reports SET status = 'resolved' WHERE post_id = ?";
        $stmt_resolve = $conn->prepare($sql_resolve);
        $stmt_resolve->bind_param("i", $post_id);
        $stmt_resolve->execute();
        $stmt_resolve->close();

        // Notify reporters
        $msg = "The reports you submitted for post ID $post_id have been reviewed and resolved by the admin.";
        foreach ($reporters_emails as $email) {
            $stmt_notif = $conn->prepare("INSERT INTO users_notification (user_email, message) VALUES (?, ?)");
            $stmt_notif->bind_param("ss", $email, $msg);
            $stmt_notif->execute();
            $stmt_notif->close();
        }

        header("Location: admin_reports.php?msg=Reports+marked+as+resolved+and+reporters+notified");
        exit();
    }
}

// Fetch post details
$sql = "SELECT * FROM admin_post WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $post = null;
} else {
    $post = $result->fetch_assoc();
}
$stmt->close();

// Fetch reports for this post to show who reported
$sql_reports = "SELECT user_email, reason, reported_at FROM admin_reports WHERE post_id = ? ORDER BY reported_at DESC";
$stmt_reports = $conn->prepare($sql_reports);
$stmt_reports->bind_param("i", $post_id);
$stmt_reports->execute();
$result_reports = $stmt_reports->get_result();
$reports = [];
while ($row = $result_reports->fetch_assoc()) {
    $reports[] = $row;
}
$stmt_reports->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>View Post #<?= htmlspecialchars($post_id) ?></title>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; }
  h1 { margin-bottom: 20px; }
  .post-container { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 0 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
  .field-label { font-weight: bold; margin-top: 10px; }
  a { color: #007bff; text-decoration: none; }
  a:hover { text-decoration: underline; }
  form { margin-top: 20px; }
  button { padding: 10px 20px; margin-right: 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
  .btn-resolve { background-color: #28a745; color: white; }
  .btn-delete { background-color: #dc3545; color: white; }
  table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
  th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
  th { background-color: #f2f2f2; }
</style>
</head>
<body>

<h1>View Post Details</h1>
<p><a href="admin_reports.php">← Back to Reports</a></p>

<?php if (!$post): ?>
    <p>Post not found or has been deleted.</p>
<?php else: ?>
    <div class="post-container">
        <div><span class="field-label">Post ID:</span> <?= htmlspecialchars($post['id']) ?></div>
        <div><span class="field-label">Title:</span> <?= htmlspecialchars($post['title']) ?></div>
        <p>Description: <?= nl2br(htmlspecialchars($post['Description'])) ?></p>
        </div>
        <div><span class="field-label">Type:</span> <?= htmlspecialchars($post['type']) ?></div>
        <div><span class="field-label">Location:</span> <?= htmlspecialchars($post['location']) ?></div>
        <div><span class="field-label">Date:</span> <?= htmlspecialchars($post['date']) ?></div>
        <?php if (!empty($post['image_path'])): ?>
        <div><span class="field-label">Image:</span><br />
            <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="Post Image" style="max-width:300px; max-height:300px;">
        </div>
        <?php endif; ?>
        <div><span class="field-label">Owner Email:</span> <?= htmlspecialchars($post['user_email']) ?></div>
        <div><span class="field-label">Created At:</span> <?= htmlspecialchars($post['created_at']) ?></div>
    </div>

    <?php if (count($reports) > 0): ?>
    <h2>Reports on this Post</h2>
    <table>
        <thead>
            <tr>
                <th>Reporter Name</th>
                <th>Reason</th>
                <th>Reported At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $report): ?>
            <tr>
                <td><?= htmlspecialchars($report['user_email']) ?></td>
                <td><?= nl2br(htmlspecialchars($report['reason'])) ?></td>
                <td><?= htmlspecialchars($report['reported_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No reports found for this post.</p>
    <?php endif; ?>

    <form method="post" onsubmit="return confirm('Are you sure you want to perform this action?');">
        <button type="submit" name="action" value="resolve" class="btn-resolve">Resolve Reports</button>
        <button type="submit" name="action" value="delete" class="btn-delete">Delete Post</button>
    </form>
<?php endif; ?>

</body>
</html>
