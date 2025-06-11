<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$item_id = $_GET['item_id'] ?? null;

if (!$item_id) {
    header("Location: view_all_posts.php");
    exit();
}

// Check if user already has a claim pending or approved for this post
$check_stmt = $conn->prepare("SELECT status FROM claims WHERE post_id = ? AND post_type = 'admin' AND user_email = ?");
$check_stmt->bind_param("is", $item_id, $username);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    $check_stmt->bind_result($existing_status);
    $check_stmt->fetch();
    $check_stmt->close();

    echo "You already have a claim with status: " . htmlspecialchars($existing_status);
    echo '<br><a href="view_all_posts.php">Back to posts</a>';
    exit();
}
$check_stmt->close();

// Insert new claim with status 'pending'
$insert_stmt = $conn->prepare("INSERT INTO claims (post_id, post_type, user_email, status) VALUES (?, 'admin', ?, 'pending')");
$insert_stmt->bind_param("is", $item_id, $username);

if ($insert_stmt->execute()) {
    echo "Your claim request has been sent to the admin. Please wait for approval.";
    echo '<br><a href="view_all_posts.php">Back to posts</a>';
} else {
    echo "Error submitting claim request. Please try again.";
}
$insert_stmt->close();
$conn->close();
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
        }
        .back-link:hover, button:hover {
            background-color: #004bbd;
        }
    </style>
</head>
<body>

<?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
    <a class="back-link" href="view_all_posts.php">Back to Posts</a>
<?php elseif ($item_id): ?>
    <form method="post" action="transaction.php">
        <input type="hidden" name="item_id" value="<?= htmlspecialchars($item_id) ?>" />
        <button type="submit" name="claim_item">Confirm Claim for Item #<?= htmlspecialchars($item_id) ?></button>
    </form>
    <a class="back-link" href="view_all_posts.php">Cancel</a>
<?php else: ?>
    <p>No item selected to claim.</p>
    <a class="back-link" href="view_all_posts.php">Back to Posts</a>
<?php endif; ?>

</body>
</html>
