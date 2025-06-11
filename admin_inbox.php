<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$admin_email = $_SESSION['username'];

$stmt = $conn->prepare("
    SELECT DISTINCT IF(sender_email = ?, receiver_email, sender_email) AS contact
    FROM messages
    WHERE sender_email = ? OR receiver_email = ?
");
$stmt->bind_param("sss", $admin_email, $admin_email, $admin_email);
$stmt->execute();
$contacts_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Inbox</title>
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
            max-width: 900px;
            margin: 60px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(8px);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        h2 {
            margin: 0;
            font-weight: 600;
            color: #007bff;
        }

        .new-message-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .new-message-btn:hover {
            background-color: #0056b3;
        }

        .top-bar a.back-link {
            text-decoration: none;
            color: #007bff;
            margin-left: 10px;
            font-weight: 500;
        }

        .top-bar a.back-link:hover {
            text-decoration: underline;
        }

        ul {
            list-style: none;
            padding: 0;
            margin-top: 30px;
        }

        li {
            margin-bottom: 16px;
            padding: 12px 16px;
            background-color: #f1f5ff;
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }

        li:hover {
            background-color: #e0eaff;
        }

        a.contact-link {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            display: block;
        }

        .no-messages {
            color: #666;
            font-style: italic;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="background-blur"></div>

<div class="container">
    <div class="top-bar">
        <h2>📨 Admin Conversations</h2>
                    <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>

        <div>
            <a class="new-message-btn" href="admin_create_message.php">➕ New Message</a>
        </div>
    </div>

    <ul>
        <?php if ($contacts_result->num_rows === 0): ?>
            <li class="no-messages">No conversations yet.</li>
        <?php else: ?>
            <?php while ($row = $contacts_result->fetch_assoc()): ?>
                <li>
                    <a class="contact-link" href="admin_chat.php?with=<?= urlencode($row['contact']) ?>">
                        <?= htmlspecialchars($row['contact']) ?>
                    </a>
                </li>
            <?php endwhile; ?>
        <?php endif; ?>
    </ul>
</div>
</body>
</html>
