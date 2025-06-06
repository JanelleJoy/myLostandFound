<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Missing report ID.");
}

$report_id = intval($_GET['id']);

// Get report details (reporter and post info)
$sql = "SELECT r.post_id, r.user_email AS reporter_email, p.user_email AS owner_email
        FROM admin_reports r
        LEFT JOIN admin_post p ON r.post_id = p.id
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$stmt->bind_result($post_id, $reporter_email, $owner_email);
$stmt->fetch();
$stmt->close();

if (!$post_id || !$reporter_email) {
    die("Invalid report or missing data.");
}

// Update report status to 'resolved'
$update_stmt = $conn->prepare("UPDATE admin_reports SET status = 'resolved' WHERE id = ?");
$update_stmt->bind_param("i", $report_id);
$update_stmt->execute();
$update_stmt->close();

// Notify reporter
if ($reporter_email) {
    $msg = "The post you reported (Post ID: $post_id) has been reviewed and resolved.";
    $stmt = $conn->prepare("INSERT INTO users_notification (user_email, message) VALUES (?, ?)");
    $stmt->bind_param("ss", $reporter_email, $msg);
    $stmt->execute();
    $stmt->close();
}

// Notify post owner
if ($owner_email) {
    $msg2 = "Your post (Post ID: $post_id) was reported and has been reviewed.";
    $stmt2 = $conn->prepare("INSERT INTO users_notification (user_email, message) VALUES (?, ?)");
    $stmt2->bind_param("ss", $owner_email, $msg2);
    $stmt2->execute();
    $stmt2->close();
}

header("Location: admin_reports.php?resolved=1");
exit();
?>
