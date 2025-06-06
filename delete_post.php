<?php
session_start();
require_once 'connect.php';

// Check admin privileges
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = "Post ID not specified.";
    header("Location: manage_posts.php");
    exit();
}

$id = intval($_GET['id']);

// Delete the post
$stmt = $conn->prepare("DELETE FROM admin_post WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$_SESSION['message'] = "Post deleted successfully.";
header("Location: admin_manage_posts.php");
exit();
