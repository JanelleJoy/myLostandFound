<?php
session_start();
require_once 'connect.php';

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Validate post ID
if (!isset($_GET['id'])) {
    die("Invalid post ID.");
}

$post_id = intval($_GET['id']);
$reported_by = $_SESSION['username'];
$error = '';

// Check for success message trigger
$success = isset($_GET['success']) && $_GET['success'] == '1';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');

    if (empty($reason)) {
        $error = "Please provide a reason for reporting.";
    } else {
        $stmt = $conn->prepare("INSERT INTO admin_reports (post_id, user_email, reason, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $post_id, $reported_by, $reason);

        if ($stmt->execute()) {
            // Redirect back to this page with success parameter
            header("Location: report_admin.php?id=$post_id&success=1");
            exit();
        } else {
            $error = "Failed to submit report. Please try again.";
        }

        $stmt->close();
    }
}

// Fetch optional post title
$post_title = '';
$stmt = $conn->prepare("SELECT title FROM admin_post WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$stmt->bind_result($post_title);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Report Admin Post</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      position: relative;
      min-height: 100vh;
      overflow: hidden;
    }
    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: url('rmmc.png') no-repeat center center fixed;
      background-size: cover;
      filter: blur(8px);
      z-index: -1;
    }
    form {
      background: rgba(255, 255, 255, 0.85);
      padding: 30px;
      border-radius: 12px;
      max-width: 600px;
      margin: 60px auto;
      box-shadow: 0 8px 16px rgba(0,0,0,0.25);
      backdrop-filter: blur(2px);
    }
    textarea {
      width: 100%;
      height: 120px;
      padding: 10px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #ccc;
      margin-bottom: 20px;
      resize: vertical;
    }
    .error {
      color: red;
      font-weight: bold;
      margin-bottom: 15px;
    }
    .success {
      color: green;
      font-weight: bold;
      margin-bottom: 15px;
    }
    button {
      background: #007BFF;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
    }
    button:hover {
      background: #0056b3;
    }
    a {
      display: block;
      margin-top: 20px;
      color: #007BFF;
      text-decoration: none;
      text-align: center;
    }
    a:hover {
      text-decoration: underline;
    }
    h2 {
      text-align: center;
      color: #333;
    }
    p strong {
      display: block;
      text-align: center;
      font-size: 18px;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<h2>Report Admin Post</h2>

<?php if (!empty($post_title)): ?>
  <p><strong>Post:</strong> <?= htmlspecialchars($post_title) ?></p>
<?php endif; ?>

<?php if ($success): ?>
  <div class="success">Report successfully submitted.</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="">
  <label for="reason">Reason for reporting:</label><br />
  <textarea name="reason" id="reason" required></textarea><br />
  <button type="submit">Submit Report</button>
</form>

<a href="admin_dashboard.php">Back to Dashboard</a>

</body>
</html>
