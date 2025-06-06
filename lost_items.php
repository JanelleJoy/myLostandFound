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

// Search term from query
$search = trim($_GET['search'] ?? '');

// Prepare SQL query with search and filter for lost items
$sql = "SELECT * FROM admin_post WHERE type = 'lost'";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (title LIKE ? OR location LIKE ?)";
    $searchParam = "%$search%";
    $params[] = &$searchParam;
    $params[] = &$searchParam;
    $types .= 'ss';
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Lost Items - Lost and Found</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
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
      max-width: 900px;
      margin: 30px auto;
      background: white;
      padding: 20px 30px 40px;
      border-radius: 10px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    h2 { margin-bottom: 20px; }
    form.search-form {
      margin-bottom: 20px;
    }
    form.search-form input[type="text"] {
      width: 70%;
      padding: 8px;
      font-size: 16px;
      border-radius: 5px 0 0 5px;
      border: 1px solid #ccc;
      border-right: none;
      box-sizing: border-box;
    }
    form.search-form button {
      padding: 9px 15px;
      font-size: 16px;
      border: 1px solid #333;
      background: #333;
      color: white;
      border-radius: 0 5px 5px 0;
      cursor: pointer;
    }
    form.search-form button:hover {
      background: #555;
    }
    .items {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
    }
    .item-card {
      background: #fff;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 0 6px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
    }
    .item-card img {
      max-width: 100%;
      max-height: 150px;
      object-fit: cover;
      border-radius: 5px;
      margin-bottom: 10px;
    }
    .item-title {
      font-weight: bold;
      font-size: 18px;
      margin-bottom: 8px;
      color: #333;
    }
    .item-desc {
      flex-grow: 1;
      font-size: 14px;
      color: #555;
      margin-bottom: 10px;
    }
    .item-info {
      font-size: 13px;
      color: #777;
    }
  </style>
</head>
<body>
  <div class="topnav">
    <div>
      <a href="user_dashboard.php">Dashboard</a>
      <a href="lost_items.php" style="background-color:#575757; border-radius:5px;">Lost Items</a>
      <a href="found_items.php">Found Items</a>
      <a href="chat.php">Chat</a>
      <a href="post_item.php">Post Item</a>
    </div>
    <div>
      <span><?= htmlspecialchars($user_name) ?> (<?= ucfirst($user_role) ?>)</span>
      <a href="logout.php" style="margin-left: 15px;">Logout</a>
    </div>
  </div>

  <div class="container">
    <h2>Lost Items</h2>

    <form method="GET" class="search-form">
      <input type="text" name="search" placeholder="Search by title or location..." value="<?= htmlspecialchars($search) ?>" />
      <button type="submit">Search</button>
    </form>

    <div class="items">
      <?php if ($result->num_rows === 0): ?>
        <p>No lost items found.</p>
      <?php else: ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="item-card">
            <?php if ($row['image'] && file_exists('./uploads/' . $row['image'])): ?>
              <img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['title']) ?>" />
            <?php else: ?>
              <img src="https://via.placeholder.com/280x150?text=No+Image" alt="No Image" />
            <?php endif; ?>
            <div class="item-title"><?= htmlspecialchars($row['title']) ?></div>
            <div class="item-desc"><?= nl2br(htmlspecialchars($row['description'])) ?></div>
            <div class="item-info">
              <strong>Location:</strong> <?= htmlspecialchars($row['location']) ?><br>
              <strong>Date:</strong> <?= htmlspecialchars($row['date']) ?><br>
              <strong>Status:</strong> <?= htmlspecialchars($row['status']) ?><br>
            </div>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
