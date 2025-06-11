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

// === Helper Redirect Function ===
function redirectToLogin() {
    header("Location: index.php");
    exit();
}

// === REGISTER ===
if (isset($_POST['register'])) {
    $name     = sanitize($_POST['name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email    = sanitize($_POST['user_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = sanitize($_POST['role'] ?? '');

    if (!$name || !$username || !$email || !$password || !$role) {
        $_SESSION['message'] = "Please fill in all required fields.";
        redirectToLogin();
    }

    // Check for duplicate username or email
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

    // Insert user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, username, user_email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $username, $email, $hashedPassword, $role);

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

    $stmt = $conn->prepare("SELECT id, name, username, password, role FROM users WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $name, $username, $hashedPassword, $role);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            // Set session
            $_SESSION['user_id']    = $id;
            $_SESSION['name']       = $name;
            $_SESSION['username']   = $username;  // corrected
            $_SESSION['user_email'] = $email;     // used in foreign keys
            $_SESSION['role']       = $role;
            $_SESSION['login_success'] = "Welcome, $username! You have successfully logged in.";

            // Notification
            $loginMessage = "You logged in at " . date("F j, Y, g:i A");
            $insertNotif = $conn->prepare("INSERT INTO users_notification (user_email, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
            $insertNotif->bind_param("ss", $email, $loginMessage);
            $insertNotif->execute();
            $insertNotif->close();

            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['message'] = "Incorrect password.";
        }
    } else {
        $_SESSION['message'] = "Email not found.";
    }

    $stmt->close();
    redirectToLogin();
}

// === Fallback ===
redirectToLogin();
$conn->close();
?>
