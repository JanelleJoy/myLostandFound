<?php
session_start();
require_once 'connect.php';

// Ensure admin access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch admin stats
$total_users = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$total_user_posts = $conn->query("SELECT COUNT(*) AS count FROM user_post")->fetch_assoc()['count'];
$total_admin_posts = $conn->query("SELECT COUNT(*) AS count FROM admin_post")->fetch_assoc()['count'];
$total_reports = $conn->query("SELECT COUNT(*) AS count FROM reports")->fetch_assoc()['count'];

// Fetch combined user and admin posts
// NOTE: Replace 'user_email' with the actual email column in admin_post if different
// If unsure, run SHOW COLUMNS FROM admin_post and adjust accordingly

$posts_sql = "
    SELECT id, title, description, image_path, location, created_at, user_email, 'user' AS type
    FROM user_post
    UNION ALL
    SELECT id, title, description, image_path, location, created_at, user_email, 'admin' AS type
    FROM admin_post
    ORDER BY created_at DESC
";

$result = $conn->query($posts_sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #f4f6f9;
      padding: 30px;
      margin: 0;
    }
    header {
      background: #007BFF;
      color: white;
      padding: 15px;
      border-radius: 8px;
      text-align: center;
      margin-bottom: 30px;
    }
    .stats {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 30px;
      justify-content: center;
    }
    .stat-card {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      text-align: center;
      min-width: 180px;
      font-weight: 600;
      color: #007BFF;
    }
    .stat-card p {
      margin: 5px 0 0;
      color: #555;
      font-weight: 400;
    }
    .features {
      margin-top: 10px;
      display: grid;
      gap: 20px;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      margin-bottom: 40px;
    }
    .feature {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      text-align: center;
      font-weight: 600;
      color: #007BFF;
    }
    .feature a {
      text-decoration: none;
      color: #007BFF;
      font-weight: 600;
    }
    .feature a:hover {
      text-decoration: underline;
    }
    .logout {
      display: block;
      margin-top: 40px;
      text-align: center;
      font-weight: 600;
    }
    .logout a {
      color: #007BFF;
      text-decoration: none;
    }
    .logout a:hover {
      text-decoration: underline;
    }
    h2 {
      color: #333;
      font-weight: 600;
      margin-top: 50px;
      margin-bottom: 20px;
      border-bottom: 2px solid #007BFF;
      padding-bottom: 5px;
    }

    .post-feed {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 20px;
    }

    .post-card {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      font-weight: 400;
      color: #333;
    }
    .post-card img {
      width: 100%;
      max-height: 220px;
      object-fit: contain;
      border-radius: 8px;
      margin-bottom: 15px;
      background-color: #f0f0f0;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      transition: max-height 0.3s ease;
    }
    @media (max-width: 768px) {
      .post-card img {
        max-height: 150px;
      }
    }
    @media (max-width: 480px) {
      .post-card img {
        max-height: 120px;
      }
    }
    .post-card h3 {
      margin: 0 0 10px 0;
      font-weight: 600;
      color: #007BFF;
    }
    .meta {
      font-size: 14px;
      color: #555;
      margin-bottom: 10px;
    }
    .source {
      font-style: italic;
      font-weight: 600;
      color: #999;
    }
  </style>
</head>
<body>

<header>
  <h1>Welcome, Admin <?= htmlspecialchars($username) ?>!</h1>
  <p>This is your Lost & Found admin dashboard.</p>
</header>

<div class="stats">
  <div class="stat-card">
    <h3><?= $total_users ?></h3>
    <p>Registered Users</p>
  </div>
  <div class="stat-card">
    <h3><?= $total_user_posts + $total_admin_posts ?></h3>
    <p>Total Posts (Users + Admin)</p>
  </div>
  <div class="stat-card">
    <h3><?= $total_reports ?></h3>
    <p>Reports Received</p>
  </div>
</div>

<div class="features">
  <div class="feature">
    <a href="admin_manage_users.php">👥 Manage Users</a>
    <p>View or remove user accounts.</p>
  </div>

  <div class="feature">
    <a href="admin_posts.php">📝 Manage Posts</a>
    <p>Moderate all posts submitted.</p>
  </div>

  <div class="feature">
    <a href="create_post.php">➕ Create Admin Post</a>
    <p>Submit lost/found post as admin.</p>
  </div>

  <div class="feature">
    <a href="admin_inbox.php">💬 Messages</a>
    <p>Chat with other users or the admin.</p>
  </div>

  <div class="feature">
    <a href="admin_reports.php">🚩 View Reports</a>
    <p>See posts reported by users.</p>
  </div>

  <div class="feature">
    <a href="admin_settings.php">⚙️ Settings</a>
    <p>Update admin preferences.</p>
  </div>
</div>

<div class="logout">
  <a href="logout.php">🔓 Logout</a>
</div>

<h2>All Posts (User & Admin)</h2>
<div class="post-feed">
  <?php while ($post = $result->fetch_assoc()): ?>
    <div class="post-card">
      <?php if (!empty($post['image_path']) && file_exists($post['image_path'])): ?>
        <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="Post Image" />
      <?php endif; ?>

      <h3><?= htmlspecialchars($post['title']) ?></h3>
      <div class="meta">
        Posted by: <?= htmlspecialchars($post['user_email']) ?> • <?= date('F j, Y', strtotime($post['created_at'])) ?>
        <span class="source"></span>
      </div>

      <p>Description: <?= nl2br(htmlspecialchars(substr($post['description'], 0, 150))) ?></p>

      <a href="report_admin.php?id=<?= $post['id'] ?>" style="color: red; font-weight: 600;">🚩 Report</a>
    </div>
  <?php endwhile; ?>
</div>

</body>
</html>
