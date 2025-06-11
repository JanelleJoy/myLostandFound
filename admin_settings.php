<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$user_name = $_SESSION['username'];
$user_email = $_SESSION['user_email'];

$message = "";
$error = "";

// Fetch last login
$stmt = $conn->prepare("SELECT last_login FROM users WHERE user_email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$stmt->bind_result($last_login);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['username']);
        $new_email = trim($_POST['email']);

        if (empty($new_username) || empty($new_email)) {
            $error = "Username and email cannot be empty.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, user_email = ? WHERE user_email = ?");
            $stmt->bind_param("sss", $new_username, $new_email, $user_email);
            if ($stmt->execute()) {
                $_SESSION['username'] = $new_username;
                $_SESSION['user_email'] = $new_email;
                $user_name = $new_username;
                $user_email = $new_email;
                $message = "Profile updated successfully.";
            } else {
                $error = "Failed to update profile.";
            }
            $stmt->close();
        }
    }

    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "Please fill in all password fields.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New password and confirmation do not match.";
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_email = ?");
            $stmt->bind_param("s", $user_email);
            $stmt->execute();
            $stmt->bind_result($hashed_password);
            $stmt->fetch();
            $stmt->close();

            if (!password_verify($current_password, $hashed_password)) {
                $error = "Current password is incorrect.";
            } else {
                $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_email = ?");
                $stmt->bind_param("ss", $new_hashed, $user_email);
                if ($stmt->execute()) {
                    $message = "Password updated successfully.";
                } else {
                    $error = "Failed to update password.";
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Settings</title>
<style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background: #eef2f7;
    padding: 40px;
    margin: 0;
  }

  .container {
    max-width: 600px;
    margin: auto;
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.05);
  }

  h1 {
    text-align: center;
    color: #004085;
    margin-bottom: 10px;
  }

  .last-login {
    text-align: center;
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 25px;
  }

  label {
    font-weight: 600;
    margin-top: 15px;
    display: block;
    color: #333;
  }

  input[type="text"], input[type="email"], input[type="password"] {
    width: 100%;
    padding: 10px;
    margin-top: 6px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
  }

  button {
    width: 100%;
    margin-top: 20px;
    padding: 12px;
    background-color: #007bff;
    border: none;
    color: white;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
  }

  .message { color: green; font-weight: 600; text-align: center; margin-top: 20px; }
  .error { color: red; font-weight: 600; text-align: center; margin-top: 20px; }

  hr {
    margin: 30px 0;
    border: none;
    border-top: 1px solid #ccc;
  }

  a {
    display: block;
    text-align: center;
    margin-top: 25px;
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
  }
</style>
</head>
<body>

<div class="container">
  <h1>Admin Settings</h1>
  <div class="last-login">Last Login: <?= htmlspecialchars($last_login ?: 'N/A') ?></div>

  <?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <!-- Profile Update Form -->
  <form method="POST" action="">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" value="<?= htmlspecialchars($user_name) ?>" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_email) ?>" required>

    <button type="submit" name="update_profile">Update Profile</button>
  </form>

  <hr>

  <!-- Password Change Form -->
  <form method="POST" action="">
    <h3>Change Password</h3>

    <label for="current_password">Current Password:</label>
    <input type="password" id="current_password" name="current_password" required>

    <label for="new_password">New Password:</label>
    <input type="password" id="new_password" name="new_password" required>

    <label for="confirm_password">Confirm New Password:</label>
    <input type="password" id="confirm_password" name="confirm_password" required>

    <button type="submit" name="change_password">Change Password</button>
  </form>

  <a href="admin_dashboard.php">← Back to Dashboard</a>
</div>

</body>
</html>
