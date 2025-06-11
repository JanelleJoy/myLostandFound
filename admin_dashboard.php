<?php
session_start();
require_once 'connect.php';

// Ensure admin access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$admin_email = $_SESSION['username'];
$admin_username = $admin_email; 

$stmt = $conn->prepare("SELECT username FROM users WHERE user_email = ?");
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$stmt->bind_result($fetched_username);
if ($stmt->fetch()) {
    $admin_username = $fetched_username;
}
$stmt->close();

$total_users = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$total_user_posts = $conn->query("SELECT COUNT(*) AS count FROM user_post")->fetch_assoc()['count'];
$total_admin_posts = $conn->query("SELECT COUNT(*) AS count FROM admin_post")->fetch_assoc()['count'];
$total_reports = $conn->query("SELECT COUNT(*) AS count FROM reports")->fetch_assoc()['count'];

$posts_sql = "
    SELECT p.id, p.title, p.description, p.image_path, p.location, p.created_at, p.user_email, 'user' AS type,
        COALESCE(c.claimed_by, '') AS claimed_by
    FROM user_post p
    LEFT JOIN claims c ON c.post_id = p.id AND c.post_type = 'user'

    UNION ALL

    SELECT p.id, p.title, p.description, p.image_path, p.location, p.created_at, p.user_email, 'admin' AS type,
        COALESCE(c.claimed_by, '') AS claimed_by
    FROM admin_post p
    LEFT JOIN claims c ON c.post_id = p.id AND c.post_type = 'admin'

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

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
<style>
html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  font-family: 'Poppins', sans-serif;
  background: transparent;
  overflow-x: hidden;
}
.background-blur {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: url('rmmc.png') no-repeat center center fixed;
  background-size: cover;
  filter: blur(14px);
  z-index: -1;
}
.main-wrapper {
  position: relative;
  margin: 30px auto;
  max-width: 1200px;
  padding: 30px;
  border-radius: 12px;
  color: #333;
  min-height: 100vh;
}
header {
  background:rgb(9, 24, 184);
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
  background: rgb(255, 255, 255);
  backdrop-filter: blur(8px);
  border-radius: 8px;
  padding: 20px;
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
  backdrop-filter: blur(8px);
  border-radius: 8px;
  padding: 20px;
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
.feature p {
  text-decoration: none;
  color:rgb(0, 0, 0);
  font-weight: 200;
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
  text-align: center;
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
.post-actions {
  margin-top: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.post-actions a {
  text-decoration: none;
  font-weight: 600;
  cursor: pointer;
}
.post-actions a.report {
  color: red;
}
.post-actions a.claim {
  color: #007BFF;
}
.post-actions span {
  font-weight: 600;
  color: green;
}
</style>

</head>
<body>

  <div class="background-blur"></div>

  <div class="main-wrapper">
<header>
  <h1>Welcome, Admin <?= htmlspecialchars($admin_username) ?>!</h1>
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
    <a href="manage_claims.php">📝 Manage Claims</a>
    <p>Moderate all posts submitted.</p>
  </div>

 <div class="feature">
    <a href="admin_manage_post.php">📝 Manage Post</a>
    <p>Moderate all posts submitted.</p>
  </div>

  <div class="feature">
    <a href="admin_create_post.php">➕ Create Post</a>
    <p>Submit lost/found post as admin.</p>
  </div>

  <div class="feature">
    <a href="admin_inbox.php">💬 Messages</a>
    <p>Chat with other users or the admin.</p>
  </div>

 

  <div class="feature">
    <a href="admin_settings.php">⚙️ Settings</a>
    <p>Update admin preferences.</p>
  </div>
</div>

<div class="logout">
  <a href="logout.php">🔓 Logout</a>
</div>


</div>

</div>

</body>
</html>
