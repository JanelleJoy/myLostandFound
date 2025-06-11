<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$post_id = $_GET['id'] ?? null;
$post_type = $_GET['type'] ?? null;

if (!$post_id || !in_array($post_type, ['user', 'admin'])) {
    die("Invalid request.");
}

$post_id = (int)$post_id;
$table = $post_type === 'user' ? 'user_post' : 'admin_post';

// Check if post exists
$stmt = $conn->prepare("SELECT claimed FROM $table WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    die("Post not found.");
}

$row = $result->fetch_assoc();
if ($row['claimed'] == 1) {
    $stmt->close();
    die("Item already claimed.");
}
$stmt->close();

// Update claimed status
$update = $conn->prepare("UPDATE $table SET claimed = 1 WHERE id = ?");
$update->bind_param("i", $post_id);
if ($update->execute()) {
    $update->close();
    header("Location: admin_dashboard.php?message=Item marked as claimed successfully");
    exit();
} else {
    $update->close();
    die("Failed to mark item as claimed: " . $conn->error);
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Claim Item</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            text-align: center;
            padding: 50px;
        }
        .message {
            font-size: 20px;
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
        }
        .back-link, button {
            margin-top: 20px;
            display: inline-block;
            padding: 10px 20px;
            background: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .back-link:hover, button:hover {
            background-color: #004bbd;
        }
    </style>
</head>
<body>

<?php if ($message): ?>
    <p class="message <?= strpos($message, '❌') === 0 ? 'error' : '' ?>">
        <?= htmlspecialchars($message) ?>
    </p>
    <a class="back-link" href="admin_dashboard.php">Back to Dashboard</a>
<?php else: ?>
    <form method="post" action="transaction_admin.php?id=<?= htmlspecialchars($post_id) ?>&type=<?= htmlspecialchars($post_type) ?>">
        <input type="hidden" name="post_id" value="<?= htmlspecialchars($post_id) ?>" />
        <input type="hidden" name="post_type" value="<?= htmlspecialchars($post_type) ?>" />
        <button type="submit" name="claim_post">Confirm Claim for Item #<?= htmlspecialchars($post_id) ?></button>
    </form>
    <a class="back-link" href="admin_dashboard.php">Cancel</a>
<?php endif; ?>

</body>
</html>
