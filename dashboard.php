<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

// Redirect based on role
if ($_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
} else {
    header("Location: user_dashboard.php");
    exit();
}
