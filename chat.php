<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['username'];
$contact = $_GET['with'] ?? '';

if (!$contact || $contact === $user_email) {
    die("Invalid contact.");
}

// Mark messages from contact as read
$update = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_email = ? AND receiver_email = ?");
$update->bind_param("ss", $contact, $user_email);
$update->execute();
$update->close();

// Send new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $message = trim($_POST['message']);
    $stmt = $conn->prepare("INSERT INTO messages (sender_email, receiver_email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $user_email, $contact, $message);
    $stmt->execute();
    $stmt->close();
}

// Fetch message history
$stmt = $conn->prepare("
    SELECT sender_email, message, sent_at 
    FROM messages 
    WHERE (sender_email = ? AND receiver_email = ?) 
       OR (sender_email = ? AND receiver_email = ?)
    ORDER BY sent_at ASC
");
$stmt->bind_param("ssss", $user_email, $contact, $contact, $user_email);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat with <?= htmlspecialchars($contact) ?></title>
    <style>
        body { font-family: Arial; background: #f9f9f9; padding: 20px; }
        .chat-box { background: #fff; padding: 20px; max-width: 600px; margin: auto; border-radius: 8px; box-shadow: 0 0 6px rgba(0,0,0,0.1); }
        .message { margin-bottom: 15px; }
        .from-me { text-align: right; }
        .from-them { text-align: left; }
        .meta { font-size: 0.75rem; color: #666; }
        textarea { width: 100%; padding: 10px; }
        button { padding: 10px 15px; background: #007bff; color: white; border: none; margin-top: 10px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

<div class="chat-box">
    <h3>Chat with <?= htmlspecialchars($contact) ?></h3>
    
    <div class="messages">
        <?php foreach ($messages as $msg): ?>
            <div class="message <?= $msg['sender_email'] === $user_email ? 'from-me' : 'from-them' ?>">
                <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                <div class="meta"><?= htmlspecialchars($msg['sent_at']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post">
        <textarea name="message" rows="3" placeholder="Type your message..." required></textarea>
        <button type="submit">Send</button>
    </form>

    <p><a href="messages.php">← Back to Messages</a></p>
</div>

</body>
</html>
