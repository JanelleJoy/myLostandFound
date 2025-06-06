<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['username'];

$stmt = $conn->prepare("
    SELECT DISTINCT IF(sender_email = ?, receiver_email, sender_email) AS contact
    FROM messages
    WHERE sender_email = ? OR receiver_email = ?
");
$stmt->bind_param("sss", $user_email, $user_email, $user_email);
$stmt->execute();
$contacts_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inbox</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 40px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 20px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .new-message-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
        .new-message-btn:hover {
            background-color: #0056b3;
        }
        .alert {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        li {
            margin-bottom: 12px;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .no-messages {
            color: #888;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($_GET['sent']) && $_GET['sent'] == '1'): ?>
        <div class="alert">✅ Message sent successfully!</div>
    <?php endif; ?>

    <div class="top-bar">
        <h2>📨 Your Conversations</h2>
    <a class="back" href="user_dashboard.php">← Back to Dashboard</a>
        <a class="new-message-btn" href="create_message.php">➕ New Message</a>
    </div>

    <ul>
        <?php if ($contacts_result->num_rows === 0): ?>
            <li class="no-messages">No conversations yet.</li>
        <?php else: ?>
            <?php while ($row = $contacts_result->fetch_assoc()): ?>
                <li>
                    <a href="user_chat.php?with=<?= urlencode($row['contact']) ?>">
                        <?= htmlspecialchars($row['contact']) ?>
                    </a>
                </li>
            <?php endwhile; ?>
        <?php endif; ?>
    </ul>
</div>
</body>
</html>
