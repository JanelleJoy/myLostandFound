<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['username'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_email)) {
        $error = "Email cannot be empty.";
    } else {
        // Verify current password before changes
        $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($current_password, $hashed_password)) {
            $error = "Current password is incorrect.";
        } else {
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = "New passwords do not match.";
                } else {
                    $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE email = ?");
                    $stmt->bind_param("sss", $new_email, $new_password_hashed, $user_email);
                }
            } else {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE email = ?");
                $stmt->bind_param("ss", $new_email, $user_email);
            }

            if (empty($error)) {
                if ($stmt->execute()) {
                    $success = "Settings updated successfully!";
                    $_SESSION['username'] = $new_email; // update session email
                    $user_email = $new_email;
                } else {
                    $error = "Error updating settings.";
                }
                $stmt->close();
            }
        }
    }
}

// Fetch current user email for form prefill
$stmt = $conn->prepare("SELECT user_email FROM users WHERE user_email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$stmt->bind_result($email);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>User Settings</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f8f9fa;
        padding: 40px;
    }
    .container {
        max-width: 500px;
        margin: auto;
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    h2 {
        margin-bottom: 20px;
    }
    label {
        display: block;
        margin-bottom: 6px;
        font-weight: bold;
    }
    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1.5px solid #ccc;
        border-radius: 6px;
    }
    button {
        background: #007bff;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
    }
    button:hover {
        background: #0056b3;
    }
    .success {
        color: green;
        margin-bottom: 15px;
    }
    .error {
        color: red;
        margin-bottom: 15px;
    }
    a.back {
        display: inline-block;
        margin-bottom: 20px;
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
    <a class="back" href="user_dashboard.php">← Back to Dashboard</a>
    <h2>User Settings</h2>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="user_setting.php">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required />

        <label for="current_password">Current Password (required to save changes)</label>
        <input type="password" id="current_password" name="current_password" required />

        <label for="new_password">New Password (leave blank to keep current)</label>
        <input type="password" id="new_password" name="new_password" />

        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" />

        <button type="submit">Save Changes</button>
    </form>
</div>

</body>
</html>
