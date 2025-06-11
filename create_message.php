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
<meta charset="UTF-8" />
<title>Start New Chat</title>
<style>
  /* Fullscreen blurred background image */
  body, html {
    margin: 0; padding: 0; height: 100%;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f8f9fa;
    overflow: hidden;
  }
  body::before {
    content: "";
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: url('rmmc.png') center center/cover no-repeat;
    filter: blur(10px);
    opacity: 0.4;
    z-index: -2;
  }
  /* Optional: a subtle dark overlay on top of image for better contrast */
  body::after {
    content: "";
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.15);
    z-index: -1;
  }

  /* Container on top */
  .container {
    position: relative;
    max-width: 600px;
    width: 100%;
    background: #fff;
    border-radius: 12px;
    padding: 30px 40px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    color: #333;
    margin: 40px auto;
    z-index: 1;
  }
  a.back {
    display: inline-block;
    margin-bottom: 25px;
    color: #007bff;
    font-weight: 600;
    text-decoration: none;
  }
  a.back:hover {
    text-decoration: underline;
  }
  h2 {
    margin-top: 0;
    margin-bottom: 30px;
    font-weight: 600;
    color: #007bff;
    text-align: center;
  }
  label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
  }
  input[type="email"], textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    resize: vertical;
    transition: border-color 0.3s ease;
  }
  input[type="email"]:focus, textarea:focus {
    outline: none;
    border-color: #007bff;
  }
  button {
    width: 100%;
    background-color: #007bff;
    color: white;
    padding: 12px 0;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background-color: #0056b3;
  }
  .error {
    color: #b30000;
    margin-bottom: 20px;
    font-weight: 600;
    text-align: center;
  }
  @media (max-width: 480px) {
    .container {
      padding: 25px 20px;
      margin: 20px;
    }
    h2 {
      font-size: 22px;
      margin-bottom: 20px;
    }
    input[type="email"], textarea {
      font-size: 13px;
    }
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

    <form method="POST" action="create_message.php" novalidate>
        <label for="receiver_email">Recipient Email:</label>
        <input type="email" name="receiver_email" id="receiver_email" required placeholder="friend@example.com" />

        <label for="message">Message:</label>
        <textarea name="message" id="message" rows="5" required placeholder="Type your message..."></textarea>

        <button type="submit">Send Message</button>
    </form>
</div>

</body>
</html>
