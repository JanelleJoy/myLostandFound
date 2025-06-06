<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$user_name = $_SESSION['username'];
$user_email = $_SESSION['user_email'] ?? '';

// Fetch last login for the logged-in user
$stmt = $conn->prepare("SELECT last_login FROM users WHERE user_email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$stmt->bind_result($last_login);
$stmt->fetch();
$stmt->close();

$message = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update username
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['username']);

        if (empty($new_username)) {
            $error = "Username cannot be empty.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ? WHERE email = ?");
            $stmt->bind_param("ss", $new_username, $user_email);
            if ($stmt->execute()) {
                $_SESSION['username'] = $new_username;
                $user_name = $new_username;
                $message = "Profile updated successfully.";
            } else {
                $error = "Failed to update profile.";
            }
            $stmt->close();
        }
    }

    // Change password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "Please fill in all password fields.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New password and confirmation do not match.";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
            $stmt->bind_param("s", $user_email);
            $stmt->execute();
            $stmt->bind_result($hashed_password);
            $stmt->fetch();
            $stmt->close();

            if (!password_verify($current_password, $hashed_password)) {
                $error = "Current password is incorrect.";
            } else {
                // Update password
                $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
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
  body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px; }
  .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
  h1 { text-align: center; margin-bottom: 20px; }
  label { display: block; margin-top: 15px; font-weight: bold; }
  input[type="text"], input[type="email"], input[type="password"] {
    width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;
  }
  button {
    margin-top: 20px; padding: 12px; width: 100%; background: #007bff; border: none; color: white; font-weight: bold; border-radius: 4px; cursor: pointer;
  }
  button:hover { background: #0056b3; }
  .message { margin-top: 15px; font-weight: bold; text-align: center; color: green; }
  .error { margin-top: 15px; font-weight: bold; text-align: center; color: red; }
  hr { margin: 30px 0; }
  a { display: inline-block; margin-top: 20px; text-decoration: none; color: #007bff; }
  a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="container">
  <h1>Admin Settings</h1>

  <?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <!-- Profile update form -->
  <form method="POST" action="">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" value="<?= htmlspecialchars($user_name) ?>" required>

    <label for="email">Email (cannot be changed):</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_email) ?>" disabled>

    <button type="submit" name="update_profile">Update Profile</button>
  </form>

  <hr>

  <!-- Password change form -->
  <form method="POST" action="">
    <h3>Change Password</h3>
    <label for="current_password">Current Password:</label>
    <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>

    <label for="new_password">New Password:</label>
    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>

    <label for="confirm_password">Confirm New Password:</label>
    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>

    <button type="submit" name="change_password">Change Password</button>
  </form>

  <p><a href="admin_dashboard.php">Back to Dashboard</a></p>
</div>

</body>
</html>
