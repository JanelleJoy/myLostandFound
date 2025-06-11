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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: transparent;
        }

        .background-blur {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('rmmc.png') no-repeat center center fixed;
            background-size: cover;
            filter: blur(14px);
            z-index: -1;
        }

        .container {
            max-width: 800px;
            margin: 60px auto;
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .top-bar h2 {
            font-size: 1.3rem;
            color: #007bff;
        }

        .top-bar a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }

        .top-bar a:hover {
            text-decoration: underline;
        }

        .messages {
            background: #f1f3f5;
            border-radius: 10px;
            padding: 20px;
            height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
            scroll-behavior: smooth;
        }

        .message {
            max-width: 70%;
            padding: 10px 16px;
            margin-bottom: 15px;
            border-radius: 16px;
            font-size: 14px;
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
            background-color: #dee2e6;
            float: left;
        }

        .timestamp {
            display: block;
            margin-top: 5px;
            font-size: 0.75rem;
            color: #666;
        }

        form textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
        }

        form button {
            margin-top: 12px;
            float: right;
            background: #007bff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        form button:hover {
            background: #0056b3;
        }

        .error {
            color: #d8000c;
            background: #ffdddd;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .success {
            color: green;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="background-blur"></div>

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
                <div class="message <?= $msg['sender_email'] === $admin_email ? 'admin' : 'user' ?>">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    <span class="timestamp"><?= htmlspecialchars($msg['timestamp']) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form method="POST" action="admin_message.php?with=<?= urlencode($selected_user) ?>">
        <input type="hidden" name="receiver_email" value="<?= htmlspecialchars($selected_user) ?>">
        <textarea name="message" rows="4" placeholder="Type your message..." required></textarea>
        <button type="submit">Send</button>
    </form>
</div>

<script>

const chatBox = document.getElementById('chat-box');
chatBox.scrollTop = chatBox.scrollHeight;
</script>
</body>
</html>
