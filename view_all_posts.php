

<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

$search_keyword = $_GET['keyword'] ?? '';
$search_type = $_GET['type'] ?? '';
$search_location = $_GET['location'] ?? '';

$sql = "SELECT * FROM admin_post WHERE 1=1";
$params = [];
$types = "";

if ($search_keyword !== '') {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $kw = '%' . $search_keyword . '%';
    $params[] = $kw;
    $params[] = $kw;
    $types .= "ss";
}

if ($search_type !== '' && in_array($search_type, ['Lost', 'Found', 'Surrender'])) {
    $sql .= " AND type = ?";
    $params[] = $search_type;
    $types .= "s";
}

if ($search_location !== '') {
    $sql .= " AND location LIKE ?";
    $params[] = '%' . $search_location . '%';
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$posts_result = $stmt->get_result();

$claim_stmt = $conn->prepare("SELECT post_id, status FROM claims WHERE user_email = ? AND post_type = 'admin'");
$claim_stmt->bind_param("s", $username);
$claim_stmt->execute();
$claims_result = $claim_stmt->get_result();

$user_claims = [];
while ($claim = $claims_result->fetch_assoc()) {
    $user_claims[$claim['post_id']] = $claim['status'];
}

$claim_stmt->close();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Search Lost & Found Posts</title>
<style>
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0 20px;
    background: none;
    position: relative;
    min-height: 100vh;
    overflow-x: hidden;
  }

  body::before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('rmmc.png') no-repeat center center fixed;
    background-size: cover;
    z-index: -1;
    filter: blur(10px);
    transform: scale(1.05);
  }

  header {
    text-align: center;
    margin-top: 40px;
    margin-bottom: 20px;
  }

  header h1, header h2 {
    background: rgba(255, 255, 255, 0.85);
    display: inline-block;
    padding: 10px 20px;
    border-radius: 10px;
    color: #002855;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  }

  nav {
    text-align: center;
    margin-bottom: 30px;
  }

  nav a {
    margin: 0 15px;
    text-decoration: none;
    color: rgb(0, 0, 0);
    font-weight: bold;
    padding: 8px 12px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: background-color 0.3s ease;
  }

  nav a:hover {
    background-color: #e2ecff;
    text-decoration: underline;
  }

  form.search-form {
    text-align: center;
    margin-bottom: 30px;
  }

  form.search-form input[type="text"],
  form.search-form select {
    padding: 10px 12px;
    margin: 0 8px 10px 8px;
    font-size: 1rem;
    border: 1px solid #ccc;
    border-radius: 6px;
    min-width: 180px;
  }

  form.search-form button {
    padding: 10px 18px;
    font-size: 1rem;
    background-color: #0d6efd;
    border: none;
    color: white;
    border-radius: 6px;
    cursor: pointer;
  }

  form.search-form button:hover {
    background-color: #0056b3;
  }

  .posts {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
    padding-bottom: 40px;
  }

  .post-card {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    padding: 16px;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease;
    overflow: hidden;
  }

  .post-card:hover {
    transform: translateY(-5px);
  }

  .post-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    display: block;
    margin-bottom: 10px;
    border-radius: 8px;
  }

  .post-title {
    font-weight: bold;
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: #002855;
  }

  .post-type {
    font-size: 1rem;
    color: #0d6efd;
    font-weight: bold;
    margin-bottom: 6px;
  }

  .post-desc {
    margin-bottom: 8px;
    white-space: pre-line;
    color: #333;
  }

  .post-location,
  .post-date,
  .post-user {
    font-size: 0.9rem;
    color: #555;
    margin-bottom: 6px;
  }

  .post-user {
    color: #333;
  }

  .post-card a.btn-claim {
    color: white;
    background-color: #007BFF;
    padding: 8px 12px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    text-align: center;
    display: inline-block;
    margin-top: 10px;
    transition: background-color 0.3s;
  }

  .post-card a.btn-claim:hover {
    background-color: #0056b3;
  }

  .claim-status {
    font-weight: bold;
    margin-top: 10px;
    font-size: 0.95rem;
  }

  .no-results {
    text-align: center;
    font-size: 1.1rem;
    margin-top: 30px;
    background-color: rgba(255,255,255,0.8);
    padding: 15px;
    border-radius: 10px;
    display: inline-block;
  }

  h1 {
    text-align: center;
    font-size: 32px;
    color: #222;
    margin-bottom: 5px;
    font-weight: 700;
  }

  h2 {
    text-align: center;
    font-size: 22px;
    color: #333;
    margin-top: 0;
    margin-bottom: 15px;
    font-weight: 600;
  }

  a.back-link {
    display: inline-block;
    font-size: 16px;
    color: rgb(5, 10, 150);
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 5px;
    font-weight: 600;
    transition: background-color 0.3s, border-color 0.3s;
  }

  a.back-link:hover {
    background-color: #007BFF;
    color: white;
    text-decoration: none;
  }
</style>

</head>
<body>

<h1>Welcome, <?= htmlspecialchars($username) ?>!</h1>
<h2>Search Lost & Found Posts</h2>
<a href="user_dashboard.php">← Back to Dashboard</a>

<form method="get" action="view_all_posts.php">
  <input type="text" name="keyword" placeholder="Search Description/Title" value="<?= htmlspecialchars($search_keyword) ?>" />
  <select name="type" id="type">
    <option value="" <?= ($search_type === '') ? 'selected' : '' ?>>All</option>
    <option value="Found" <?= ($search_type === 'Found') ? 'selected' : '' ?>>Found</option>
    <option value="Lost" <?= ($search_type === 'Lost') ? 'selected' : '' ?>>Lost</option>
    <option value="Surrender" <?= ($search_type === 'Surrender') ? 'selected' : '' ?>>Surrender</option>
  </select>
  <input type="text" name="location" placeholder="Location" value="<?= htmlspecialchars($search_location) ?>" />
  <button type="submit">Search</button>
</form>

<?php if ($posts_result->num_rows === 0): ?>
  <p class="no-results">No posts match your search criteria.</p>
<?php else: ?>
  <div class="posts">
    <?php while ($post = $posts_result->fetch_assoc()): ?>
      <div class="post-card">
        <?php if ($post['image_path'] && file_exists($post['image_path'])): ?>
          <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="Post Image" />
        <?php endif; ?>

        <div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
        <div class="post-type"><?= htmlspecialchars($post['type']) ?></div>
        <div class="post-desc"><?= nl2br(htmlspecialchars($post['Description'])) ?></div>
        <div class="post-location">Location: <?= htmlspecialchars($post['location']) ?></div>
        <div class="post-date">Posted on: <?= htmlspecialchars($post['created_at']) ?></div>
        <div class="post-user">Posted by: <?= htmlspecialchars($post['user_email']) ?></div>

        <?php
          $post_id = $post['id'];
          $type = strtolower($post['type']);
          $can_claim = in_array($type, ['found', 'surrender']);
          $claim_status = $user_claims[$post_id] ?? null;
        ?>

        <?php if ($can_claim): ?>
          <?php if ($claim_status === 'approved'): ?>
            <div class="claim-status" style="color: green;">✔️ Claim Approved</div>
          <?php elseif ($claim_status === 'pending'): ?>
            <div class="claim-status" style="color: orange;">⏳ Claim Pending</div>
          <?php elseif ($claim_status === 'rejected'): ?>
            <div class="claim-status" style="color: red;">❌ Claim Rejected</div>
            <a href="claim_post.php?item_id=<?= $post_id ?>&post_type=admin" class="btn-claim">Try Claim Again</a>
          <?php else: ?>
            <a href="claim_post.php?item_id=<?= $post_id ?>&post_type=admin" class="btn-claim">Claim</a>
          <?php endif; ?>
        <?php endif; ?>

      </div>
    <?php endwhile; ?>
  </div>
<?php endif; ?>

</body>
</html>

