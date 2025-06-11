<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$admin_email = $_SESSION['username'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_email = trim($_POST['receiver_email']);
    $message = trim($_POST['message']);

    if (empty($receiver_email) || empty($message)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (sender_email, receiver_email, message, timestamp, is_read) VALUES (?, ?, ?, NOW(), 0)");
        $stmt->bind_param("sss", $admin_email, $receiver_email, $message);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_chat.php?with=" . urlencode($receiver_email));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send New Message</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        * {
            box-sizing: border-box;
        }

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
            max-width: 600px;
            margin: 80px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(8px);
        }

        h2 {
            margin-bottom: 25px;
            color: #007bff;
            font-weight: 600;
        }

        a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 20px;
        }

        a:hover {
            text-decoration: underline;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-family: inherit;
            font-size: 14px;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        .error {
            color: #d8000c;
            background-color: #ffdddd;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="background-blur"></div>

<div class="container">
    <h2>📨 Send New Message</h2>
    <a href="admin_inbox.php">← Back to Inbox</a>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="receiver_email">Receiver Email</label>
        <input type="email" name="receiver_email" id="receiver_email" required>

        <label for="message">Message</label>
        <textarea name="message" id="message" rows="6" required></textarea>

        <button type="submit">Send</button>
    </form>
</div>
</body>
</html>
