<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['user_email'];
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : null;
$post_type = $_GET['post_type'] ?? 'admin';

if (!$item_id || !in_array($post_type, ['admin', 'surrendered'])) {
    header("Location: view_all_posts.php");
    exit();
}

// Check if already claimed
$stmt = $conn->prepare("SELECT status, receipt_code FROM claims WHERE post_id = ? AND post_type = ? AND user_email = ?");
$stmt->bind_param("iss", $item_id, $post_type, $user_email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($existing_status, $existing_receipt);
    $stmt->fetch();
    $stmt->close();
    echo <<<HTML
    <div style="font-family: Arial; padding: 20px; max-width: 600px; margin: auto; background: #f9f9f9; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center;">
        <h2 style="color: #007BFF;">Claim Already Submitted</h2>
        <p>Status: <strong style="color: #FF8800;">{$existing_status}</strong></p>
        <p>Receipt Code: <strong style="font-size: 20px; color: green;">{$existing_receipt}</strong></p>
        <p>Present this code with your School ID and proof of ownership.</p>
        <br>
        <a href="view_all_posts.php" style="padding: 10px 20px; background-color: #007BFF; color: white; text-decoration: none; border-radius: 5px;">Back to Posts</a>
    </div>
    HTML;
    exit();
}
$stmt->close();

// Create new claim
try {
    $receipt_code = strtoupper(bin2hex(random_bytes(4))); // 8-char alphanumeric
} catch (Exception $e) {
    die("Failed to generate receipt code.");
}

$insert = $conn->prepare("INSERT INTO claims (post_id, post_type, user_email, status, receipt_code) VALUES (?, ?, ?, 'pending', ?)");
$insert->bind_param("isss", $item_id, $post_type, $user_email, $receipt_code);

if ($insert->execute()) {
    echo <<<HTML
    <div style="font-family: Arial; padding: 20px; max-width: 600px; margin: auto; background: #f0fff0; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center;">
        <h2 style="color: green;">Claim Submitted!</h2>
        <p>Status: <strong style="color: #FFA500;">PENDING</strong></p>
        <p>Claim Code:</p>
        <p style="font-size: 24px; color: #007BFF; font-weight: bold;">{$receipt_code}</p>
        <p>Bring School ID and proof of ownership to the office.</p>
        <br>
        <a href="user_dashboard.php" style="padding: 10px 20px; background-color: #007BFF; color: white; text-decoration: none; border-radius: 5px;">Back to Dashboard</a>
    </div>
    HTML;
} else {
    echo <<<HTML
    <div style="font-family: Arial; padding: 20px; max-width: 600px; margin: auto; background: #ffe6e6; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center;">
        <h2 style="color: red;">Error</h2>
        <p>There was an error submitting your claim. Please try again.</p>
        <a href="view_all_posts.php" style="margin-top: 10px; padding: 10px 20px; background-color: #007BFF; color: white; text-decoration: none; border-radius: 5px;">Back to Posts</a>
    </div>
    HTML;
}

$insert->close();
$conn->close();
?>
