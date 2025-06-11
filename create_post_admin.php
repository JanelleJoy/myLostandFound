<?php
// Include database connection
require_once 'connect.php';

$success = '';
$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $imagePath = null;

    // Validate input
    if (empty($title) || empty($type) || empty($description) || empty($location)) {
        $error = "All fields except image are required.";
    } else {
        // Handle image upload if provided
        if (!empty($_FILES['image']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $imageName = basename($_FILES['image']['name']);
            $targetFile = $targetDir . time() . "_" . $imageName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = $targetFile;
            } else {
                $error = "Image upload failed.";
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO posts (title, type, description, location, image_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", $title, $type, $description, $location, $imagePath);

            if ($stmt->execute()) {
                $success = "Post created successfully!";
                $_POST = []; // Clear POST values
            } else {
                $error = "Failed to create post: " . $conn->error;
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Post - Admin</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: url('rmmc.png') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
        }

        .overlay {
            backdrop-filter: blur(8px);
            background-color: rgba(0, 0, 0, 0.4);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.85);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            width: 400px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        select,
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        button[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background-color: #004c99;
        }

        .message {
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
            padding: 10px;
            border-radius: 8px;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="form-container">
            <h1>Create a New Post</h1>

     <a href="user_dashboard.php">← Back to Dashboard</a>
     
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($error)): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($success)): ?>
                <div class="message success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" novalidate>
                <label for="title">Title</label>
                <input type="text" name="title" id="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">

                <label for="type">Type</label>
                <select name="type" id="type" required>
                    <option value="" disabled <?= empty($_POST['type']) ? 'selected' : '' ?>>-- Select Type --</option>
                    <option value="Lost" <?= ($_POST['type'] ?? '') === 'Lost' ? 'selected' : '' ?>>Lost</option>
                    <option value="Found" <?= ($_POST['type'] ?? '') === 'Found' ? 'selected' : '' ?>>Found</option>
                    <option value="Surrendered" <?= ($_POST['type'] ?? '') === 'Surrendered' ? 'selected' : '' ?>>Surrendered</option>
                </select>

                <label for="description">Description</label>
                <textarea name="description" id="description" rows="4" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

                <label for="location">Location</label>
                <input type="text" name="location" id="location" required value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">

                <label for="image">Image (optional)</label>
                <input type="file" name="image" id="image" accept="image/*">

                <button type="submit">Submit Post</button>
            </form>

        </div>
    </div>
</body>
</html>
