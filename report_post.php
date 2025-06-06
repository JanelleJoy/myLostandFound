<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$post_id = $_GET['id'] ?? null;

if (!$post_id) {
    echo "Invalid post ID.";
    exit();
}

$user_email = $_SESSION['email'] ?? $_SESSION['username'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason']);

    if ($reason === '') {
        $error = "Please provide a reason for reporting.";
    } else {
        // Insert the report
        $stmt = $conn->prepare("INSERT INTO admin_reports (post_id, user_email, reason) VALUES (?, ?, ?)");
        if (!$stmt) {
            $error = "Database error: " . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param("iss", $post_id, $user_email, $reason);
            if ($stmt->execute()) {
                $stmt->close();

                // Get post owner's email
                $post_stmt = $conn->prepare("SELECT user_email FROM admin_post WHERE id = ?");
                $post_stmt->bind_param("i", $post_id);
                $post_stmt->execute();
                $post_stmt->bind_result($post_owner_email);
                $post_stmt->fetch();
                $post_stmt->close();

                // Send notification to post owner
                if ($post_owner_email) {
                    $notif_stmt = $conn->prepare("INSERT INTO users_notification (user_email, message) VALUES (?, ?)");
                    $notif_message = "Your post (ID: $post_id) has been reported for review.";
                    $notif_stmt->bind_param("ss", $post_owner_email, $notif_message);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }

                $_SESSION['message'] = "Post reported successfully.";
                header("Location: users_notifocation.php");
                exit();
            } else {
                $error = "Failed to report post: " . htmlspecialchars($stmt->error);
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Report Post #<?= htmlspecialchars($post_id) ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; }
        label { font-weight: bold; }
        textarea { width: 100%; }
        .error { color: red; }
        button { padding: 10px 15px; background-color: #dc3545; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #c82333; }
    </style>
</head>
<body>
    <h2>Report Post #<?= htmlspecialchars($post_id) ?></h2>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" novalidate>
        <label for="reason">Reason for Reporting:</label><br>
        <textarea name="reason" id="reason" rows="5" placeholder="Explain why this post is misleading or false..." required><?= isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : '' ?></textarea><br><br>
        <button type="submit">Submit Report</button>
    </form>
</body>
</html>
