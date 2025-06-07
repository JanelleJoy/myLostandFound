<?php
session_start();
require_once 'connect.php';

// Check if user is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$admin_email = $_SESSION['username'];
$selected_user = $_GET['with'] ?? null;
$message_sent = false;
$error = '';

// Send message handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_email'], $_POST['message'])) {
    $receiver_email = $_POST['receiver_email'];
    $message = trim($_POST['message']);

    if ($message === '') {
        $error = "Message cannot be empty.";
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (sender_email, receiver_email, message, timestamp, is_read) VALUES (?, ?, ?, NOW(), 0)");
        $stmt->bind_param("sss", $admin_email, $receiver_email, $message);
        $stmt->execute();
        $stmt->close();

        $message_sent = true;
        $selected_user = $receiver_email;
    }
}

// Fetch all distinct users who have messaged or been messaged by admin
$stmt = $conn->prepare("
    SELECT DISTINCT IF(sender_email = ?, receiver_email, sender_email) AS contact
    FROM messages
    WHERE sender_email = ? OR receiver_email = ?
    ORDER BY timestamp DESC
");
$stmt->bind_param("sss", $admin_email, $admin_email, $admin_email);
$stmt->execute();
$contacts_result = $stmt->get_result();
$stmt->close();

// Fetch chat messages if user selected
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

    // Mark messages from user as read
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_email = ? AND receiver_email = ?");
    $stmt->bind_param("ss", $selected_user, $admin_email);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Messages</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f6f9;
        padding: 30px;
    }
    .container {
        max-width: 900px;
        margin: auto;
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
        display: flex;
        gap: 20px;
    }
    .contacts {
        width: 250px;
        border-right: 1px solid #ddd;
        overflow-y: auto;
        max-height: 600px;
    }
    .contacts h2 {
        margin-top: 0;
        font-size: 1.2em;
    }
    .contacts ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .contacts li {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    .contacts a {
        text-decoration: none;
        color: #007bff;
        display: block;
    }
    .contacts a.selected {
        font-weight: bold;
        background-color: #e9ecef;
        border-radius: 4px;
        padding-left: 8px;
    }
    .chat {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        max-height: 600px;
    }
    .chat-header {
        font-weight: bold;
        margin-bottom: 10px;
    }
    .messages {
        flex-grow: 1;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 15px;
        background: #f9f9f9;
        margin-bottom: 15px;
    }
    .message {
        margin-bottom: 10px;
        max-width: 70%;
        padding: 8px 12px;
        border-radius: 15px;
        clear: both;
    }
    .message.admin {
        background: #007bff;
        color: white;
        float: right;
        text-align: right;
    }
    .message.user {
        background: #e2e3e5;
        color: #333;
        float: left;
        text-align: left;
    }
    .timestamp {
        font-size: 0.7em;
        color: #666;
        margin-top: 2px;
    }
    form textarea {
        width: 100%;
        height: 70px;
        padding: 10px;
        resize: vertical;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 14px;
    }
    form button {
        background: #007bff;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
        margin-top: 8px;
        float: right;
    }
    form button:hover {
        background: #0056b3;
    }
    .error {
        color: red;
        margin-bottom: 10px;
    }
    .message-sent {
        color: green;
        margin-bottom: 10px;
    }
</style>
</head>
<body>

<div class="container">
    <div class="contacts">
        <h2>Users</h2>
        <ul>
            <?php if ($contacts_result->num_rows === 0): ?>
                <li>No conversations yet.</li>
            <?php else: ?>
                <?php while ($contact = $contacts_result->fetch_assoc()): ?>
                    <li>
                        <a href="?with=<?= urlencode($contact['contact']) ?>"
                           class="<?= ($selected_user === $contact['contact']) ? 'selected' : '' ?>">
                           <?= htmlspecialchars($contact['contact']) ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            <?php endif; ?>
        </ul>
    </div>

    <div class="chat">
        <?php if ($selected_user): ?>
            <div class="chat-header">
                Chat with <?= htmlspecialchars($selected_user) ?>
            </div>

            <div class="messages" id="messages">
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
                <input type="hidden" name="receiver_email" value="<?= htmlspecialchars($selected_user) ?>" />
                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php elseif ($message_sent): ?>
                    <div class="message-sent">Message sent!</div>
                <?php endif; ?>
                <textarea name="message" placeholder="Type your message..." required></textarea>
                <button type="submit">Send</button>
            </form>
        <?php else: ?>
            <p>Select a user from the left to start chatting.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Scroll chat messages to bottom on page load
const messagesDiv = document.getElementById('messages');
if (messagesDiv) {
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}
</script>

</body>
</html>
