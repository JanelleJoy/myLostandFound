<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// === DB Connection ===
$host = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'users_db';

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// === Sanitize Helper ===
function sanitize($input) {
    return htmlspecialchars(trim($input));
}

// === REGISTER ===
if (isset($_POST['register'])) {
    $username = sanitize($_POST['username'] ?? '');
    $email    = sanitize($_POST['user_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = sanitize($_POST['role'] ?? '');

    if (!$username || !$email || !$password || !$role) {
        $_SESSION['message'] = "Please fill in all required fields.";
        redirectToLogin();
    }

    // Check for duplicates
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR user_email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['message'] = "Username or email already exists.";
        $stmt->close();
        redirectToLogin();
    }
    $stmt->close();

    // Insert new user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, user_email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Registration successful. Please log in.";
    } else {
        $_SESSION['message'] = "Registration failed. Please try again.";
    }

    $stmt->close();
    redirectToLogin();
}

// === LOGIN ===
if (isset($_POST['login'])) {
    $email    = sanitize($_POST['user_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $_SESSION['message'] = "Please enter email and password.";
        redirectToLogin();
    }

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $username, $hashedPassword, $role);
        $stmt->fetch();

    if (password_verify($password, $hashedPassword)) {
  // After successful password verification
$_SESSION['user_id'] = $id;
$_SESSION['username'] = $username;
$_SESSION['role'] = $role;
$_SESSION['login_success'] = "Welcome, $username! You have successfully logged in.";
header("Location: dashboard.php");
exit();


}
 else {
            $_SESSION['message'] = "Incorrect password.";
        }
    } else {
        $_SESSION['message'] = "Email not found.";
    }

    $stmt->close();
    redirectToLogin();
}

// === Fallback Redirect ===
redirectToLogin();

// === Helper Redirect Function ===
function redirectToLogin() {
    header("Location: index.php");
    exit();
}

$conn->close();
?>
