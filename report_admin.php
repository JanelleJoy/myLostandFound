<?php
session_start();
require_once 'connect.php';

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Validate post ID
if (!isset($_GET['id'])) {
    die("Invalid post ID.");
}

$post_id = intval($_GET['id']);
$reported_by = $_SESSION['username'];
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');

    if (empty($reason)) {
        $error = "Please provide a reason for reporting.";
    } else {
        // Insert into admin_reports (make sure your DB table matches this schema)
        $stmt = $conn->prepare("INSERT INTO admin_reports (post_id, user_email, reason, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $post_id, $reported_by, $reason);

        if ($stmt->execute()) {
            // Redirect immediately to admin_reports.php
            header("Location: admin_reports.php");
            exit();
        } else {
            $error = "Failed to submit report. Please try again.";
        }

        $stmt->close();
    }
}

// Fetch optional post title (to show context)
$post_title = '';
$stmt = $conn->prepare("SELECT title FROM admin_post WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$stmt->bind_result($post_title);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Report Admin Post</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f9f9f9; }
    form { background: white; padding: 20px; border-radius: 8px; max-width: 500px; margin: auto; }
    textarea { width: 100%; height: 100px; margin-bottom: 15px; }
    .error { color: red; margin-bottom: 10px; }
    button { background: #007BFF; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
    button:hover { background: #0056b3; }
    a { display: inline-block; margin-top: 15px; color: #007BFF; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>

<h2>Report Admin Post</h2>

<?php if (!empty($post_title)): ?>
  <p><strong>Post:</strong> <?= htmlspecialchars($post_title) ?></p>
<?php endif; ?>

<?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="">
  <label for="reason">Reason for reporting:</label><br />
  <textarea name="reason" id="reason" required></textarea><br />
  <button type="submit">Submit Report</button>
</form>

<a href="admin_dashboard.php">Back to Dashboard</a>

</body>
</html>
