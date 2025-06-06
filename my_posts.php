<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['email'] ?? '';
$username = $_SESSION['username'];
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Handle deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Verify the post belongs to this user
    $stmt = $conn->prepare("SELECT image_path FROM admin_post WHERE id = ? AND user_email = ?");
    $stmt->bind_param("is", $delete_id, $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $post = $result->fetch_assoc();
        if ($post['image_path'] && file_exists($post['image_path'])) {
            unlink($post['image_path']);
        }

        $stmt_del = $conn->prepare("DELETE FROM admin_post WHERE id = ? AND user_email = ?");
        $stmt_del->bind_param("is", $delete_id, $user_email);
        $stmt_del->execute();
        $stmt_del->close();

        $_SESSION['message'] = "Post deleted successfully.";
        header("Location: my_posts.php");
        exit();
    } else {
        $message = "Post not found or permission denied.";
    }
    $stmt->close();
}

// Fetch user's posts
$stmt = $conn->prepare("SELECT * FROM admin_post WHERE user_email = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>My Posts - Lost & Found</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    padding: 30px;
  }
  h1 {
    text-align: center;
    margin-bottom: 30px;
  }
  .message {
    color: green;
    text-align: center;
    margin-bottom: 20px;
  }
  .posts {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(300px,1fr));
    gap: 20px;
  }
  .post-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    padding: 15px;
  }
  .post-card img {
    max-width: 100%;
    height: auto;
    border-radius: 6px;
    margin-bottom: 10px;
  }
  .post-title {
    font-weight: bold;
    font-size: 1.2em;
    margin-bottom: 6px;
  }
  .post-type {
    font-size: 0.9em;
    color: #007BFF;
    font-weight: bold;
    margin-bottom: 6px;
  }
  .post-desc {
    margin-bottom: 10px;
    white-space: pre-line;
  }
  .post-location {
    font-style: italic;
    color: #555;
    margin-bottom: 10px;
  }
  .post-date {
    font-size: 0.8em;
    color: #999;
    margin-bottom: 10px;
  }
  .actions a {
    margin-right: 10px;
    text-decoration: none;
    color: #007BFF;
    font-weight: bold;
  }
  .actions a.delete {
    color: red;
  }
  .back-link {
    display: block;
    text-align: center;
    margin-top: 30px;
  }
</style>
</head>
<body>

<h1>My Posts</h1>

<?php if ($message): ?>
  <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($result->num_rows === 0): ?>
  <p style="text-align:center;">You haven't created any posts yet.</p>
<?php else: ?>
  <div class="posts">
    <?php while ($post = $result->fetch_assoc()): ?>
      <div class="post-card">
        <?php if ($post['image_path'] && file_exists($post['image_path'])): ?>
          <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="Post Image">
        <?php endif; ?>
        <div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
        <div class="post-type"><?= htmlspecialchars($post['type']) ?></div>
        <div class="post-desc"><?= nl2br(htmlspecialchars($post['description'])) ?></div>
        <div class="post-location">Location: <?= htmlspecialchars($post['location']) ?></div>
        <div class="post-date">Posted on: <?= htmlspecialchars($post['created_at']) ?></div>
        <div class="actions">
          <a href="edit_post_user.php?id=<?= $post['id'] ?>">Edit</a>
          <a href="?delete_id=<?= $post['id'] ?>" class="delete" onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
<?php endif; ?>

<div class="back-link">
  <a href="user_dashboard.php">← Back to Dashboard</a>
</div>

</body>
</html>
