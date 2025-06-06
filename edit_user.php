<?php
session_start();
require_once 'connect.php';

// Ensure admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Redirect if no ID
if (!isset($_GET['id'])) {
    header("Location: admin_users.php");
    exit();
}

$user_id = intval($_GET['id']);
$message = "";

// Fetch user
$stmt = $conn->prepare("SELECT id, name, user_email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    $message = "User not found.";
} else {
    $user = $result->fetch_assoc();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    if ($name && $email && in_array($role, ['user', 'admin'])) {
        $update = $conn->prepare("UPDATE users SET name=?, user_email=?, role=? WHERE id=?");
        $update->bind_param("sssi", $name, $email, $role, $user_id);
        if ($update->execute()) {
            $_SESSION['message'] = "User updated successfully.";
            header("Location: admin_manage_users.php");
            exit();
        } else {
            $message = "Update failed. Try again.";
        }
    } else {
        $message = "All fields are required and valid.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
 

</head>
<body>
<h2>Edit User</h2>

<?php if ($message) echo "<p style='color:red;'>$message</p>"; ?>

<form method="POST">
    <label>Name:</label><br />
    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required><br><br>

    <label>Email:</label><br />
    <input type="email" name="email" value="<?= htmlspecialchars($user['user_email']) ?>" required><br><br>

    <label>Role:</label><br />
    <select name="role" required>
        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select><br><br>

    <button type="submit">Update User</button>
</form>

<p><a href="admin_manage_users.php">Back to User List</a></p>
</body>
</html>
