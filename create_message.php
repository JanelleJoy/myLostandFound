<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username'])) {
    // Replace this with real login check in production
    $_SESSION['username'] = 'user1@example.com'; // TEMP fallback for testing
}

$user_email = $_SESSION['username'];

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_email = trim($_POST['receiver_email']);
    $message = trim($_POST['message']);

    if (!empty($receiver_email) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_email, receiver_email, message, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $user_email, $receiver_email, $message);
        $stmt->execute();
        $stmt->close();

        // Redirect directly to chat view after sending
        header("Location: user_chat.php?with=" . urlencode($receiver_email));
        exit();
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Start New Chat</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 40px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        input[type="email"], textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error {
            color: #b30000;
            margin-bottom: 15px;
        }
        a.back {
            display: inline-block;
            margin-bottom: 15px;
            color: #007bff;
            text-decoration: none;
        }
        a.back:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <a class="back" href="messages.php">← Back to Inbox</a>
    <h2>➕ Start New Chat</h2>

    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="create_message.php">
        <label for="receiver_email">Recipient Email:</label>
        <input type="email" name="receiver_email" id="receiver_email" required placeholder="friend@example.com">

        <label for="message">Message:</label>
        <textarea name="message" id="message" rows="4" required placeholder="Type your message..."></textarea>

        <button type="submit">Send Message</button>
    </form>
</div>
</body>
</html>
