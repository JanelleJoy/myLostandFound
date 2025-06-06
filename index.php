<?php
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    header("Location: dashboard.php");
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
  * {
    box-sizing: border-box;
  }
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f4f8;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
  }

  .container {
    background: #fff;
    max-width: 400px;
    width: 100%;
    border-radius: 8px;
    padding: 30px 40px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
  }

  h2 {
    margin-bottom: 25px;
    color: #333;
    text-align: center;
    font-weight: 700;
  }

  form {
    display: flex;
    flex-direction: column;
  }

  label {
    margin-bottom: 6px;
    font-weight: 600;
    color: #555;
  }

  input[type="text"],
  input[type="email"],
  input[type="password"],
  select {
    padding: 12px 14px;
    margin-bottom: 18px;
    border: 1.8px solid #ddd;
    border-radius: 6px;
    font-size: 15px;
    transition: border-color 0.3s ease;
  }

  input[type="text"]:focus,
  input[type="email"]:focus,
  input[type="password"]:focus,
  select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
  }

  button {
    background-color: #007bff;
    border: none;
    color: white;
    padding: 14px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-top: 10px;
  }
  button:hover {
    background-color: #0056b3;
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
    user-select: none;
  }

  .hidden {
    display: none;
  }

  .message {
    text-align: center;
    margin-bottom: 15px;
    color: #d93025; /* Google red */
    font-weight: 600;
  }
</style>
</head>
<body>

<div class="container">
  <h2>Login</h2>

  <?php
  // Show message if any (e.g. errors or success)
  if (isset($_SESSION['message'])) {
      echo "<p class='message'>{$_SESSION['message']}</p>";
      unset($_SESSION['message']);
  }
  ?>

  <form id="loginForm" method="POST" action="login_register.php">
      <label for="login_email">Email</label>
      <input type="email" id="login_email" name="user_email" required>

      <label for="login_password">Password</label>
      <input type="password" id="login_password" name="password" required>

      <button type="submit" name="login">Login</button>

      <div class="toggle-text">
          Don't have an account? <span id="showRegister">Register</span>
      </div>
  </form>

  <form id="registerForm" method="POST" action="login_register.php" class="hidden">
     

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
  document.getElementById('showRegister').onclick = () => {
      document.getElementById('loginForm').classList.add('hidden');
      document.querySelector('h2').textContent = 'Register';
      document.getElementById('registerForm').classList.remove('hidden');
  };

  document.getElementById('showLogin').onclick = () => {
      document.getElementById('registerForm').classList.add('hidden');
      document.querySelector('h2').textContent = 'Login';
      document.getElementById('loginForm').classList.remove('hidden');
  };
</script>

</body>
</html>
