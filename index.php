<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$host = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'users_db';

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Sanitize helper
function sanitize($input) {
    return htmlspecialchars(trim($input));
}

// Handle register
if (isset($_POST['register'])) {
    $name     = sanitize($_POST['name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email    = sanitize($_POST['user_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = sanitize($_POST['role'] ?? '');

    if (!$username || !$email || !$password || !$role || !$name) {
        $_SESSION['message'] = "Please fill in all fields.";
    } else {
        // Check for duplicate username/email
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR user_email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $_SESSION['message'] = "Username or email already exists.";
        } else {
            // Insert new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, username, user_email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $username, $email, $hashedPassword, $role);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Registration successful. You can now log in.";
            } else {
                $_SESSION['message'] = "Registration failed. Try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
    header("Location: index.php");
    exit();
}

// Handle login
if (isset($_POST['login'])) {
    $email = sanitize($_POST['user_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $_SESSION['message'] = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, username, password, role FROM users WHERE user_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $name, $username, $hashedPassword, $role);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['name'] = $name;
                $_SESSION['username'] = $username;
                $_SESSION['user_email'] = $email;
                $_SESSION['role'] = $role;

                header("Location: dashboard.php");
                exit();
            } else {
                $_SESSION['message'] = "Incorrect password.";
            }
        } else {
            $_SESSION['message'] = "Email not found.";
        }
        $stmt->close();
    }
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login / Register</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: 'Courier New', monospace;
      background: url('rmmc.png') no-repeat center center fixed;
      background-size: cover;
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
      padding: 20px;
    }

    .container {
      background: white;
      border-radius: 12px;
      padding: 30px 40px;
      max-width: 420px;
      width: 100%;
      color: #000;
      position: relative;
      z-index: 1;
    }

    h2 {
      margin-bottom: 20px;
      color: black;
      text-align: center;
      font-weight: bold;
    }

    form {
      display: flex;
      flex-direction: column;
    }

    label {
      margin-bottom: 5px;
      font-weight: 600;
      color: black;
    }

    input, select {
      padding: 12px;
      margin-bottom: 16px;
      border: 1.5px solid #ccc;
      border-radius: 5px;
      font-size: 15px;
    }

    input:focus, select:focus {
      outline: none;
      border-color: #007bff;
      box-shadow: 0 0 5px rgba(0,123,255,0.4);
    }

    button {
      background-color: rgb(32, 12, 187);
      color: white;
      border: none;
      padding: 14px;
      font-size: 16px;
      font-weight: bold;
      border-radius: 6px;
      cursor: pointer;
      margin-top: 10px;
    }

    button:hover {
      background-color: darkblue;
    }

    .toggle-text {
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
      color: #666;
    }

    .toggle-text span {
      color: #007bff;
      cursor: pointer;
      text-decoration: underline;
    }

    .hidden {
      display: none;
    }

    .message {
      text-align: center;
      margin-bottom: 15px;
      color: #d93025;
      font-weight: bold;
    }

    .logo {
      display: block;
      margin: 0 auto 20px;
      max-width: 100px;
    }
  </style>
</head>
<body>

<div class="container">
  <img src="rmmc.png" alt="Logo" class="logo" />
  <h2 id="formTitle">Login</h2>

  <?php
    if (isset($_SESSION['message'])) {
      echo "<p class='message'>{$_SESSION['message']}</p>";
      unset($_SESSION['message']);
    }
  ?>

  <!-- Login Form -->
  <form id="loginForm" method="POST">
    <label for="login_email">Email</label>
    <input type="email" id="login_email" name="user_email" required>

    <label for="login_password">Password</label>
    <input type="password" id="login_password" name="password" required>

    <button type="submit" name="login">Login</button>

    <div class="toggle-text">
      Don't have an account? <span id="showRegister">Register</span>
    </div>
  </form>

  <!-- Register Form -->
  <form id="registerForm" method="POST" class="hidden">
    <label for="register_name">Full Name</label>
    <input type="text" id="register_name" name="name" required>

    <label for="register_username">Username</label>
    <input type="text" id="register_username" name="username" required>

    <label for="register_email">Email</label>
    <input type="email" id="register_email" name="user_email" required>

    <label for="register_password">Password</label>
    <input type="password" id="register_password" name="password" required>

    <label for="register_role">Role</label>
    <select id="register_role" name="role" required>
      <option value="" disabled selected>Select Role</option>
      <option value="user">User</option>
      <option value="admin">Admin</option>
    </select>

    <button type="submit" name="register">Register</button>

    <div class="toggle-text">
      Already have an account? <span id="showLogin">Login</span>
    </div>
  </form>
</div>

<script>
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');
  const formTitle = document.getElementById('formTitle');

  document.getElementById('showRegister').onclick = () => {
    loginForm.classList.add('hidden');
    registerForm.classList.remove('hidden');
    formTitle.textContent = 'Register';
  };

  document.getElementById('showLogin').onclick = () => {
    registerForm.classList.add('hidden');
    loginForm.classList.remove('hidden');
    formTitle.textContent = 'Login';
  };
</script>

</body>
</html>
