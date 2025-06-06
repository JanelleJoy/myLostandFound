<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: index.html");
    exit();
}

$user_email = $_SESSION['user_email'];
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? 'user';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $date = $_POST['date'] ?? '';
    $type = $_POST['type'] ?? '';  // 'lost' or 'found'
    $status = 'Not Claimed'; // Default status

    // Validate required fields
    if (!$title || !$description || !$location || !$date || !in_array($type, ['lost', 'found'])) {
        $error = "Please fill in all fields correctly.";
    } else {
        // Handle image upload if any
        $imageName = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $fileSize = $_FILES['image']['size'];
            $fileType = $_FILES['image']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = './uploads/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                $destPath = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $imageName = $newFileName;
                } else {
                    $error = "There was an error uploading the image.";
                }
            } else {
                $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
            }
        }

        if (!$error) {
            // Insert into DB
            $stmt = $conn->prepare("INSERT INTO admin_post (user_email, title, description, location, date, type, image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssssss", $user_email, $title, $description, $location, $date, $type, $imageName, $status);

            if ($stmt->execute()) {
                $success = "Item posted successfully!";
            } else {
                $error = "Failed to post item. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Post Item - Lost and Found</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; background: #f5f5f5; }
    .topnav {
      background-color: #333; color: white; display: flex; justify-content: space-between; padding: 14px 20px; align-items: center;
    }
    .topnav a {
      color: white; padding: 10px 14px; text-decoration: none; font-weight: bold;
    }
    .topnav a:hover { background-color: #575757; border-radius: 5px; }
    .container {
      max-width: 600px;
      margin: 30px auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    h2 { margin-bottom: 20px; }
    form label {
      display: block;
      margin-bottom: 6px;
      font-weight: bold;
    }
    form input[type="text"],
    form input[type="date"],
    form select,
    form textarea {
      width: 100%;
      padding: 8px;
      margin-bottom: 15px;
      border-radius: 5px;
      border: 1px solid #ccc;
      box-sizing: border-box;
      font-size: 14px;
    }
    form textarea {
      resize: vertical;
      min-height: 100px;
    }
    form input[type="file"] {
      margin-bottom: 15px;
    }
    form button {
      background-color: #333;
      color: white;
      border: none;
      padding: 12px 20px;
      font-size: 16px;
      border-radius: 5px;
      cursor: pointer;
    }
    form button:hover {
      background-color: #555;
    }
    .message {
      padding: 10px 15px;
      border-radius: 5px;
      margin-bottom: 15px;
    }
    .error {
      background-color: #f8d7da;
      color: #721c24;
    }
    .success {
      background-color: #d4edda;
      color: #155724;
    }
  </style>
</head>
<body>

  <div class="topnav">
    <div>
      <a href="user_dashboard.php">Dashboard</a>
      <a href="lost_items.php">Lost Items</a>
      <a href="found_items.php">Found Items</a>
      <a href="chat.php">Chat</a>
      <a href="post_item.php" style="background-color:#575757; border-radius:5px;">Post Item</a>
    </div>
    <div>
      <span><?= htmlspecialchars($user_name) ?> (<?= ucfirst($user_role) ?>)</span>
      <a href="logout.php" style="margin-left: 15px;">Logout</a>
    </div>
  </div>

  <div class="container">
    <h2>Post a Lost or Found Item</h2>

    <?php if ($error): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <label for="type">Item Type</label>
      <select id="type" name="type" required>
        <option value="">Select Type</option>
        <option value="lost" <?= (isset($_POST['type']) && $_POST['type'] == 'lost') ? 'selected' : '' ?>>Lost</option>
        <option value="found" <?= (isset($_POST['type']) && $_POST['type'] == 'found') ? 'selected' : '' ?>>Found</option>
      </select>

      <label for="title">Title</label>
      <input type="text" id="title" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" />

      <label for="description">Description</label>
      <textarea id="description" name="description" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

      <label for="location">Location</label>
      <input type="text" id="location" name="location" required value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" />

      <label for="date">Date</label>
      <input type="date" id="date" name="date" required value="<?= htmlspecialchars($_POST['date'] ?? '') ?>" />

      <label for="image">Upload Image (optional)</label>
      <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif" />

      <button type="submit">Post Item</button>
    </form>
  </div>

</body>
</html>
