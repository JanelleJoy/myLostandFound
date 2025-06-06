<?php
session_start();
require_once 'connect.php';

// Check if the user is logged in (you can adjust this logic for admins only)
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$reporter = $_SESSION['username'];
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$source = isset($_GET['source']) ? $_GET['source'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason']);

    if ($post_id && $reason && in_array($source, ['admin', 'users'])) {
        $stmt = $conn->prepare("INSERT INTO reports (post_id, source, report_reason, reported_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $post_id, $source, $reason, $reporter);

        if ($stmt->execute()) {
            echo "<p style='color: green;'>Report submitted successfully.</p>";
        } else {
            echo "<p style='color: red;'>Failed to submit report.</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color: red;'>Invalid input.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Report Post</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }
        .report-container {
            background: white;
            max-width: 500px;
            margin: auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            margin-top: 0;
            color: #c0392b;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 15px;
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>

<div class="report-container">
    <h2>🚩 Report This Post</h2>
    <form method="POST">
        <label for="reason">Reason for Reporting:</label>
        <textarea name="reason" id="reason" required placeholder="Describe the issue with this post..."></textarea>
        <button type="submit">Submit Report</button>
    </form>
</div>

</body>
</html>
