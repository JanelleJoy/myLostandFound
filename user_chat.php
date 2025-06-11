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
        $stmt = $conn->prepare("INSERT INTO messages (sender_email, receiver_email, message, timestamp, is_read) VALUES (?, ?, ?, NOW(), 0)");
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

// Mark messages as read
$stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_email = ? AND receiver_email = ? AND is_read = 0");
$stmt->bind_param("ss", $chat_with, $user_email);
$stmt->execute();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Chat with <?= htmlspecialchars($chat_with) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
<style>
  html, body {
    margin: 0; padding: 0; height: 100%;
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
    position: relative;
    max-width: 800px;
    margin: 40px auto 60px auto;
    background: rgba(255,255,255,0.6);
    border-radius: 12px;
    padding: 30px 40px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    backdrop-filter: blur(8px);
    color: #333;
    min-height: 80vh;
    display: flex;
    flex-direction: column;
  }
  a.back-link {
    color: #007bff;
    font-weight: 600;
    text-decoration: none;
    margin-bottom: 25px;
    display: inline-block;
  }
  a.back-link:hover {
    text-decoration: underline;
  }
  h2 {
    color: rgb(9, 24, 184);
    font-weight: 600;
    font-size: 28px;
    margin: 0 0 30px 0;
    text-align: center;
  }
  .messages-wrapper {
    flex-grow: 1;
    overflow-y: auto;
    margin-bottom: 30px;
    padding-right: 10px;
  }
  .message {
    max-width: 70%;
    padding: 14px 18px;
    margin-bottom: 20px;
    border-radius: 12px;
    background-color: #f1f3f5;
    color: #333;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    word-wrap: break-word;
  }
  .message.you {
    background-color: #d1ecf1;
    margin-left: auto;
    color: #034f5f;
    box-shadow: 0 2px 10px rgba(3,79,95,0.2);
  }
  .message strong {
    font-weight: 600;
  }
  .timestamp {
    font-size: 12px;
    color: #6c757d;
    margin-top: 6px;
    font-style: italic;
  }
  form {
    display: flex;
    flex-direction: column;
  }
  textarea {
    resize: vertical;
    min-height: 80px;
    max-height: 200px;
    padding: 14px 18px;
    font-size: 16px;
    border-radius: 10px;
    border: 1.5px solid #ccc;
    font-family: 'Poppins', sans-serif;
    transition: border-color 0.3s ease;
  }
  textarea:focus {
    outline: none;
    border-color: #007bff;
  }
  button {
    margin-top: 15px;
    padding: 14px 0;
    font-weight: 600;
    font-size: 16px;
    color: white;
    background-color: rgb(9, 24, 184);
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background-color: rgb(7, 18, 140);
  }
  @media (max-width: 600px) {
    .container {
      margin: 20px 15px 40px 15px;
      padding: 25px 25px 25px 25px;
    }
    h2 {
      font-size: 22px;
      margin-bottom: 20px;
    }
    .message {
      max-width: 85%;
      padding: 12px 14px;
      margin-bottom: 16px;
    }
    textarea {
      font-size: 14px;
    }
  }
</style>
</head>
<body>

<div class="background-blur"></div>

<div class="container">
    <a class="back-link" href="messages.php">← Back to Inbox</a>
    <h2>💬 Chat with <?= htmlspecialchars($chat_with) ?></h2>

    <div class="messages-wrapper" id="messagesWrapper">
        <?php if ($messages->num_rows === 0): ?>
            <p style="font-style: italic; text-align: center; color: #555;">No messages yet. Start the conversation below.</p>
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
    </div>

    <form action="user_chat.php?with=<?= urlencode($chat_with) ?>" method="POST">
        <textarea name="message" rows="4" placeholder="Type your message..." required></textarea>
        <button type="submit">Send</button>
    </form>
</div>

<script>
  // Auto scroll to bottom of messages
  const wrapper = document.getElementById('messagesWrapper');
  if(wrapper) {
    wrapper.scrollTop = wrapper.scrollHeight;
  }
</script>

</body>
</html>
