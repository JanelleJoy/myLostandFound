<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and role is 'user'
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['username']; // Assuming username is the email
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $type = $_POST['type'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $image_path = '';

    // Basic validation
    if (empty($title) || empty($type) || empty($description) || empty($location)) {
        $error = "All fields are required.";
    } else {
        // Handle image upload if provided
        if (!empty($_FILES['image']['name'])) {
            $upload_dir = 'uploads/';
            $original_name = basename($_FILES['image']['name']);
            $safe_name = preg_replace('/[^A-Za-z0-9_.-]/', '_', $original_name);
            $target_file = $upload_dir . time() . "_" . $safe_name;

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($imageFileType, $allowed_types)) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
            }
        }

        // Insert post if no error
        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO admin_post (title, type, description, location, image_path, user_email, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssss", $title, $type, $description, $location, $image_path, $user_email);

            if ($stmt->execute()) {
                $message = "✅ Post created successfully!";
            } else {
                $error = "❌ Failed to create post. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Post</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f9f9f9;
    padding: 40px;
  }
  h2 {
    text-align: center;
  }
  form {
    background: white;
    padding: 25px;
    max-width: 600px;
    margin: auto;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  }
  label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
  }
  input, textarea, select {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border-radius: 5px;
    border: 1px solid #ccc;
  }
  button {
    margin-top: 20px;
    width: 100%;
    padding: 12px;
    background: #007BFF;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
  }
  button:hover {
    background: #0056b3;
  }
  .message {
    text-align: center;
    color: green;
    margin-bottom: 10px;
  }
  .error {
    text-align: center;
    color: red;
    margin-bottom: 10px;
  }
  .back {
    text-align: center;
    margin-top: 20px;
  }
</style>
</head>
<body>

<h2>Create Lost/Found Post</h2>

<?php if ($message): ?>
  <div class="message"><?= htmlspecialchars($message) ?></div>
<?php elseif ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <label for="title">Title</label>
  <input type="text" name="title" id="title" required>

  <label for="type">Type</label>
  <select name="type" id="type" required>
    <option value="">-- Select Type --</option>
    <option value="Lost">Lost</option>
    <option value="Found">Found</option>
  </select>

  <label for="description">Description</label>
  <textarea name="description" id="description" rows="4" required></textarea>

  <label for="location">Location</label>
  <input type="text" name="location" id="location" required>

  <label for="image">Image (optional)</label>
  <input type="file" name="image" id="image" accept="image/*">

  <button type="submit">Submit Post</button>
</form>

<div class="back">
  <a href="user_dashboard.php">← Back to Dashboard</a>
</div>

</body>
</html>
