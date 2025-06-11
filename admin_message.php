<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$admin_email = $_SESSION['username'];
$selected_user = $_GET['with'] ?? null;
$error = '';
$message_sent = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_email'], $_POST['message'])) {
    $receiver_email = trim($_POST['receiver_email']);
    $message = trim($_POST['message']);

    if ($message === '') {
        $error = "Message cannot be empty.";
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (sender_email, receiver_email, message, timestamp, is_read) VALUES (?, ?, ?, NOW(), 0)");
        $stmt->bind_param("sss", $admin_email, $receiver_email, $message);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_message.php?with=" . urlencode($receiver_email) . "&sent=1");
        exit();
    }
}

// Fetch conversation
$chat_messages = [];
if ($selected_user) {
    $stmt = $conn->prepare("
        SELECT sender_email, receiver_email, message, timestamp 
        FROM messages 
        WHERE (sender_email = ? AND receiver_email = ?) 
           OR (sender_email = ? AND receiver_email = ?) 
        ORDER BY timestamp ASC
    ");
    $stmt->bind_param("ssss", $admin_email, $selected_user, $selected_user, $admin_email);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $chat_messages[] = $row;
    }
    $stmt->close();

    // Mark as read
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_email = ? AND receiver_email = ?");
    $stmt->bind_param("ss", $selected_user, $admin_email);
    $stmt->execute();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Chat</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
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
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-bar a {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
        .messages {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            height: 400px;
            overflow-y: auto;
            margin: 20px 0;
            background-color: #f9f9f9;
        }
        .message {
            max-width: 70%;
            margin-bottom: 15px;
            padding: 10px 14px;
            border-radius: 14px;
            position: relative;
            clear: both;
        }
        .admin {
            background-color: #007bff;
            color: white;
            float: right;
            text-align: right;
        }
        .user {
            background-color: #e2e3e5;
            float: left;
        }
        .timestamp {
            font-size: 0.75em;
            color: #555;
            margin-top: 5px;
        }
        form textarea {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            resize: vertical;
            font-size: 14px;
            border: 1px solid #ccc;
        }
        form button {
            margin-top: 10px;
            float: right;
            padding: 10px 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        form button:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .success {
            color: green;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="top-bar">
        <a href="admin_inbox.php">← Back to Inbox</a>
        <h2>Chat with <?= htmlspecialchars($selected_user ?? '...') ?></h2>
    </div>

    <?php if (isset($_GET['sent'])): ?>
        <div class="success">✅ Message sent successfully!</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="messages" id="chat-box">
        <?php if (empty($chat_messages)): ?>
            <p>No messages yet.</p>
        <?php else: ?>
            <?php foreach ($chat_messages as $msg): ?>
                <div class="message <?= ($msg['sender_email'] === $admin_email) ? 'admin' : 'user' ?>">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    <div class="timestamp"><?= htmlspecialchars($msg['timestamp']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form method="POST" action="admin_message.php?with=<?= urlencode($selected_user) ?>">
        <input type="hidden" name="receiver_email" value="<?= htmlspecialchars($selected_user) ?>">
        <textarea name="message" placeholder="Type your message..." required></textarea>
        <button type="submit">Send</button>
    </form>
</div>

<script>
// Auto-scroll to bottom
const chatBox = document.getElementById('chat-box');
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
</script>
</body>
</html>
