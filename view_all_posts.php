<?php
session_start();
require_once 'connect.php';

// Only logged-in users (both 'user' and 'admin') can view posts
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

// Initialize search variables
$search_keyword = $_GET['keyword'] ?? '';
$search_type = $_GET['type'] ?? '';
$search_location = $_GET['location'] ?? '';

// Build SQL with filters
$sql = "SELECT * FROM admin_post WHERE 1=1";
$params = [];
$types = "";

// Filter by keyword in title or description
if ($search_keyword !== '') {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $kw = '%' . $search_keyword . '%';
    $params[] = $kw;
    $params[] = $kw;
    $types .= "ss";
}

// Filter by type (Lost or Found)
if ($search_type !== '' && in_array($search_type, ['Lost', 'Found'])) {
    $sql .= " AND type = ?";
    $params[] = $search_type;
    $types .= "s";
}

// Filter by location (partial match)
if ($search_location !== '') {
    $sql .= " AND location LIKE ?";
    $params[] = '%' . $search_location . '%';
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

// Prepare and bind
$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Search Lost & Found Posts</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    padding: 20px;
  }
  header {
    text-align: center;
    margin-bottom: 30px;
  }
  form.search-form {
    margin-bottom: 30px;
    text-align: center;
  }
  form.search-form input[type="text"],
  form.search-form select {
    padding: 8px 10px;
    margin-right: 10px;
    font-size: 1em;
    border: 1px solid #ccc;
    border-radius: 4px;
    min-width: 180px;
  }
  form.search-form button {
    padding: 8px 15px;
    font-size: 1em;
    background-color: #007BFF;
    border: none;
    color: white;
    border-radius: 4px;
    cursor: pointer;
  }
  form.search-form button:hover {
    background-color: #0056b3;
  }
  .posts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
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
    font-size: 1.3em;
    margin-bottom: 6px;
  }
  .post-type {
    font-size: 1em;
    color: #007BFF;
    font-weight: bold;
    margin-bottom: 8px;
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
    font-size: 0.85em;
    color: #999;
    margin-bottom: 10px;
  }
  .post-user {
    font-size: 0.9em;
    color: #333;
    margin-bottom: 10px;
  }
  nav {
    margin-bottom: 20px;
    text-align: center;
  }
  nav a {
    margin: 0 10px;
    text-decoration: none;
    color: #007BFF;
    font-weight: bold;
  }
  nav a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>

<header>
  <h1>Welcome, <?= htmlspecialchars($username) ?>!</h1>
  <h2>Search Lost & Found Posts</h2>
</header>

<nav>
  <a href="user_dashboard.php">Dashboard</a>
  <a href="logout.php">Logout</a>
</nav>

<form method="get" action="view_all_posts.php">
  <input type="text" name="keyword" placeholder="Search Description/Title" value="<?= htmlspecialchars($search_keyword) ?>" />
  <select name="type" id="type">
    <option value="All" <?= ($_GET['type'] ?? '') === 'All' ? 'selected' : '' ?>>All</option>
    <option value="Found" <?= ($_GET['type'] ?? '') === 'Found' ? 'selected' : '' ?>>Found</option>
    <option value="Lost" <?= ($_GET['type'] ?? '') === 'Lost' ? 'selected' : '' ?>>Lost</option>
  </select>
  <input type="text" name="location" placeholder="Location" value="<?= htmlspecialchars($search_location) ?>" />
  <button type="submit">Search</button>
</form>

<?php if ($result->num_rows === 0): ?>
  <p style="text-align:center;">No posts match your search criteria.</p>
<?php else: ?>
  <div class="posts">
    <?php while ($post = $result->fetch_assoc()): ?>
      <div class="post-card">
        <?php if ($post['image_path'] && file_exists($post['image_path'])): ?>
          <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="Post Image">
        <?php endif; ?>
        <div class="post-title">Title: <?= htmlspecialchars($post['title']) ?></div>
        <div class="post-type"><?= htmlspecialchars($post['type']) ?></div>
        <div class="post-desc">Description: <?= nl2br(htmlspecialchars($post['Description'])) ?></div>
        <div class="post-location">Location: <?= htmlspecialchars($post['location']) ?></div>
        <div class="post-date">Posted on: <?= htmlspecialchars($post['created_at']) ?></div>
        <div class="post-user">Posted by: <?= htmlspecialchars($post['user_email']) ?></div>
        <a href="report_post.php?id=<?= $post['id'] ?>" style="color: red;">Report</a>

      </div>
    <?php endwhile; ?>
  </div>
<?php endif; ?>

</body>
</html>
