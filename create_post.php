<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $type = $_POST['type'];
    $location = trim($_POST['location']);
    $user_email = $_SESSION['username'];

    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_tmp = $_FILES['image']['tmp_name'];
        $image_name = basename($_FILES['image']['name']);
        $upload_dir = 'uploads/';
        $target_file = $upload_dir . time() . "_" . $image_name;

        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($image_tmp, $target_file)) {
            $image_path = $target_file;
        } else {
            $error = "Image upload failed.";
        }
    }

    if ($title && $description && $type && $location && !$error) {
        $stmt = $conn->prepare("INSERT INTO admin_post (title, description, type, location, image_path, user_email, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $title, $description, $type, $location, $image_path, $user_email);
        $stmt->execute();
        $_SESSION['message'] = "Post created successfully!";
        header("Location: admin_posts.php");
        exit();
    } elseif (!$error) {
        $error = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create New Post</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 40px; background: #f9f9f9; }
    form { background: white; padding: 20px; max-width: 600px; margin: auto; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    input, textarea, select { width: 100%; padding: 10px; margin-top: 10px; }
    button { margin-top: 15px; padding: 10px 20px; background: #007BFF; color: white; border: none; border-radius: 4px; }
    .error { color: red; }
    .success { color: green; }
    a { display: inline-block; margin-top: 20px; }
  </style>
</head>
<body>

<h2>Create New Post</h2>

<?php if ($error): ?>
  <p class="error"><?= $error ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <label>Title:
    <input type="text" name="title" required>
  </label>

  <label>Description:
    <textarea name="description" rows="6" required></textarea>
  </label>

  <label>Type:
    <select name="type" required>
      <option value="">-- Select Type --</option>
      <option value="Lost">Lost</option>
      <option value="Found">Found</option>
    </select>
  </label>

  <label>Location:
    <input type="text" name="location" required>
  </label>

  <label>Upload Image:
    <input type="file" name="image" accept="image/*">
  </label>

  <button type="submit">Publish Post</button>
</form>

<p><a href="admin_dashboard.php">← Back to Dashboard</a></p>

</body>
</html>
