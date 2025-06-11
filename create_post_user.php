<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and role is 'user'
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['username'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $type = $_POST['type'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $image_path = '';

    if (empty($title) || empty($type) || empty($description) || empty($location)) {
        $error = "All fields are required.";
    } else {
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

        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO admin_post (title, type, description, location, image_path, user_email, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssss", $title, $type, $description, $location, $image_path, $user_email);

            if ($stmt->execute()) {
    if ($type === 'Found') {
        $message = "✅ Post created successfully!<br>PLEASE SURRENDER THE FOUND ITEM IN THE OFFICE!";
    } else {
        $message = "✅ Post created successfully!";
    }

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
<meta charset="UTF-8" />
<title>Create Lost/Found Post</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
<style>
  /* Reset and base */
  html, body {
    margin: 0;
    padding: 0;
    height: 100%;
    font-family: 'Poppins', sans-serif;
    background: transparent;
    overflow-x: hidden;
  }

  /* Background blur like dashboard */
  .background-blur {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: url('rmmc.png') no-repeat center center fixed;
    background-size: cover;
    filter: blur(14px);
    z-index: -1;
  }

  /* Main wrapper with frosted glass effect */
  .main-wrapper {
    position: relative;
    margin: 30px auto;
    max-width: 600px;
    padding: 30px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(8px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    color: #333;
    min-height: 100vh;
  }

  /* Header style consistent with dashboard */
  header {
    background: rgb(9, 70, 184);
    color: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 30px;
    font-weight: 600;
  }

  /* Form styles */
  form {
    display: flex;
    flex-direction: column;
  }

  label {
    font-weight: 600;
    margin-top: 15px;
    margin-bottom: 6px;
    color: #007BFF;
  }

  input[type="text"],
  select,
  textarea,
  input[type="file"] {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 16px;
    font-family: 'Poppins', sans-serif;
  }

  textarea {
    resize: vertical;
  }

  button {
    margin-top: 25px;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: rgb(9, 24, 184);
    color: white;
    font-weight: 600;
    font-size: 18px;
    cursor: pointer;
    transition: background 0.3s ease;
  }

  button:hover {
    background: rgb(7, 18, 140);
  }

  /* Messages */
  .message, .error {
    text-align: center;
    font-weight: 600;
    margin-bottom: 20px;
    padding: 10px;
    border-radius: 8px;
  }

  .message {
    color: #155724;
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
  }

  .error {
    color: #721c24;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
  }

  /* Back link */
  .back {
    text-align: center;
    margin-top: 30px;
  }
  .back a {
    color: #007BFF;
    text-decoration: none;
    font-weight: 600;
  }
  .back a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>

<div class="background-blur"></div>

<div class="main-wrapper">
<header>
  <h1>Create Lost/Found Post</h1>
</header>

<?php if ($message): ?>
<div class="message"><?= htmlspecialchars_decode($message) ?></div>
<?php elseif ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" novalidate>
  <label for="title">Title</label>
  <input type="text" name="title" id="title" required value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">

  <label for="type">Type</label>
  <select name="type" id="type" required>
    <option value="" disabled <?= !isset($_POST['type']) ? 'selected' : '' ?>>-- Select Type --</option>
    <option value="Lost" <?= (isset($_POST['type']) && $_POST['type'] === 'Lost') ? 'selected' : '' ?>>Lost</option>
    <option value="Found" <?= (isset($_POST['type']) && $_POST['type'] === 'Found') ? 'selected' : '' ?>>Found</option>
  </select>

  <label for="description">Description</label>
  <textarea name="description" id="description" rows="4" required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>

  <label for="location">Location</label>
  <input type="text" name="location" id="location" required value="<?= isset($_POST['location']) ? htmlspecialchars($_POST['location']) : '' ?>">

  <label for="image">Image (optional)</label>
  <input type="file" name="image" id="image" accept="image/*">

  <button type="submit">Submit Post</button>
</form>

<div class="back">
  <a href="user_dashboard.php">← Back to Dashboard</a>
</div>
</div>

</body>
</html>
