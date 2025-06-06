<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['username'];

if (!isset($_GET['with'])) {
    header("Location: messages.php");
    exit();
}

$chat_with = $_GET['with'];

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_email, receiver_email, message, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $user_email, $chat_with, $message);
        $stmt->execute();
        $stmt->close();
        header("Location: user_chat.php?with=" . urlencode($chat_with));
        exit();
    }
}

// Fetch conversation messages
$stmt = $conn->prepare("
    SELECT sender_email, receiver_email, message, timestamp
    FROM messages
    WHERE (sender_email = ? AND receiver_email = ?) OR (sender_email = ? AND receiver_email = ?)
    ORDER BY timestamp ASC
");
$stmt->bind_param("ssss", $user_email, $chat_with, $chat_with, $user_email);
$stmt->execute();
$messages = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat with <?= htmlspecialchars($chat_with) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 40px;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 16px;
            padding: 12px;
            border-radius: 8px;
            background-color: #f1f3f5;
        }
        .message.you {
            background-color: #d1ecf1;
        }
        .timestamp {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }
        form {
            margin-top: 30px;
        }
        textarea {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            border: 1.5px solid #ccc;
            border-radius: 6px;
            resize: vertical;
            margin-bottom: 10px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <a class="back-link" href="messages.php">← Back to Inbox</a>
    <h2>💬 Chat with <?= htmlspecialchars($chat_with) ?></h2>

    <?php if ($messages->num_rows === 0): ?>
        <p><em>No messages yet. Start the conversation below.</em></p>
    <?php else: ?>
        <?php while ($row = $messages->fetch_assoc()): ?>
            <div class="message <?= $row['sender_email'] === $user_email ? 'you' : '' ?>">
                <strong><?= htmlspecialchars($row['sender_email']) ?>:</strong><br>
                <?= nl2br(htmlspecialchars($row['message'])) ?>
                <div class="timestamp">
                    <?= date("F j, Y - g:i A", strtotime($row['timestamp'])) ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <form action="user_chat.php?with=<?= urlencode($chat_with) ?>" method="POST">
        <textarea name="message" rows="4" placeholder="Type your message..." required></textarea>
        <button type="submit">Send</button>
    </form>
</div>
</body>
</html>
