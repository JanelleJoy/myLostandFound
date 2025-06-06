<?php
session_start();
require_once 'connect.php';

// Check admin privileges
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = "Post ID not specified.";
    header("Location: admin_posts.php");
    exit();
}

$id = intval($_GET['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $type = trim($_POST['type']);

    if ($title && $description && $type) {
        $stmt = $conn->prepare("UPDATE admin_post SET title = ?, description = ?, type = ? WHERE id = ?");
        $stmt->bind_param("sssi", $title, $description, $type, $id);
        $stmt->execute();
        $_SESSION['message'] = "Post updated successfully.";
        header("Location: admin_posts.php");
        exit();
    } else {
        $error = "All fields are required.";
    }
}

// Fetch post data
$stmt = $conn->prepare("SELECT * FROM admin_post WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    $_SESSION['message'] = "Post not found.";
    header("Location: admin_posts.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Post</title>
</head>
<body>
<h1>Edit Post</h1>
<p><a href="admin_posts.php">Back to Manage Posts</a></p>

<?php if (isset($error)): ?>
  <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="post">
  <label>Title:<br>
    <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
  </label><br><br>
  
  <label>Description:<br>
    <textarea name="description" rows="6" required><?php echo htmlspecialchars($post['description']); ?></textarea>
  </label><br><br>

  <label>Type:<br>
    <input type="text" name="type" value="<?php echo htmlspecialchars($post['type']); ?>" required>
  </label><br><br>

  <button type="submit">Update Post</button>
</form>

</body>
</html>
