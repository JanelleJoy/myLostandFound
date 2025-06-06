<?php
session_start();
require_once 'connect.php';

// Only admin can access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Must provide a user ID
if (!isset($_GET['id'])) {
    $_SESSION['message'] = "User ID not provided.";
    header("Location: admin_users.php");
    exit();
}

$user_id = intval($_GET['id']);

// Prevent deleting yourself (optional safety check)
if ($_SESSION['user_id'] == $user_id) {
    $_SESSION['message'] = "You cannot delete your own account.";
    header("Location: admin_users.php");
    exit();
}

// Delete user
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $_SESSION['message'] = "User deleted successfully.";
} else {
    $_SESSION['message'] = "Failed to delete user.";
}

header("Location: admin_manage_users.php");
exit();
