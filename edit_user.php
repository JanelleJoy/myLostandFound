<?php
session_start();
require_once 'connect.php';

// Redirect if not admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Redirect if no ID provided
if (!isset($_GET['id'])) {
    header("Location: admin_users.php");
    exit();
}

$user_id = intval($_GET['id']);
$message = "";

// Fetch user data including username
$stmt = $conn->prepare("SELECT id, name, username, user_email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $message = "User not found.";
} else {
    $user = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

        $update = $conn->prepare("UPDATE users SET name=?, username=?, user_email=?, role=? WHERE id=?");
        $update->bind_param("ssssi", $name, $username, $email, $role, $user_id);
        if ($update->execute()) {
            $_SESSION['message'] = "User updated successfully.";
            header("Location: admin_manage_users.php");
            exit();
        } else {
            $message = "Update failed. Please try again.";
        }
    } else {
        $message = "All fields are required and must be valid.";
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background: transparent;
            overflow-x: hidden;
        }

        .background-blur {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('rmmc.png') no-repeat center center fixed;
            background-size: cover;
            filter: blur(14px);
            z-index: -1;
        }

        .main-wrapper {
            max-width: 500px;
            margin: 40px auto;
            padding: 30px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            color: #333;
        }

        h2 {
            text-align: center;
            color: #0930b8;
            margin-bottom: 25px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 600;
            margin-top: 15px;
            color: #0056b3;
        }

        input, select {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
            margin-top: 5px;
        }

        button {
            margin-top: 25px;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: rgb(9, 24, 184);
            color: white;
            font-weight: 600;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: rgb(7, 18, 140);
        }

        .message {
            text-align: center;
            color: red;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .back-link {
            text-align: center;
            margin-top: 25px;
        }

        .back-link a {
            text-decoration: none;
            color: #0056b3;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="background-blur"></div>

<div class="main-wrapper">
    <h2>Edit User</h2>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

        <label>Username:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['user_email']) ?>" required>

       

        <button type="submit">Update User</button>
    </form>

    <div class="back-link">
        <a href="admin_manage_users.php">← Back to User List</a>
    </div>
</div>

</body>
</html>
