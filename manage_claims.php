<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claim_id = $_POST['claim_id'] ?? null;
    $new_status = $_POST['status'] ?? null;

    if ($claim_id && in_array($new_status, ['approved', 'rejected'])) {
        $update = $conn->prepare("UPDATE claims SET status = ? WHERE id = ?");
        $update->bind_param("si", $new_status, $claim_id);
        $update->execute();
        $update->close();
    }

    header("Location: manage_claims.php");
    exit();
}

$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['post_type'] ?? '';
$search_query = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = [];
$params = [];
$types = '';

if ($status_filter !== '') {
    $where[] = 'c.status = ?';
    $params[] = $status_filter;
    $types .= 's';
}

if ($type_filter !== '') {
    $where[] = 'c.post_type = ?';
    $params[] = $type_filter;
    $types .= 's';
}

if ($search_query !== '') {
    $where[] = '(c.user_email LIKE ? OR c.receipt_code LIKE ?)';
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $types .= 'ss';
}

if ($date_from !== '') {
    $where[] = '(COALESCE(a.created_at, s.surrendered_date) >= ?)';
    $params[] = $date_from;
    $types .= 's';
}
if ($date_to !== '') {
    $where[] = '(COALESCE(a.created_at, s.surrendered_date) <= ?)';
    $params[] = $date_to;
    $types .= 's';
}

$where_clause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
SELECT 
    c.id AS claim_id, c.status, c.user_email, c.receipt_code, c.post_type,
    COALESCE(a.title, s.item_name) AS title,
    COALESCE(a.type, s.item_type) AS type,
    COALESCE(a.location, s.location) AS location,
    COALESCE(a.description, s.description) AS description,
    COALESCE(a.image_path, s.image_path) AS image_path,
    COALESCE(a.created_at, s.surrendered_date) AS created_at
FROM claims c
LEFT JOIN admin_post a ON c.post_type = 'admin' AND c.post_id = a.id
LEFT JOIN surrendered_items s ON c.post_type = 'surrendered' AND c.post_id = s.id
$where_clause
ORDER BY 
    FIELD(c.status, 'pending', 'approved', 'rejected'), 
    created_at DESC
";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Claims</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #007BFF;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007BFF;
            text-decoration: none;
        }
        form.filter-form {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        form.filter-form input, form.filter-form select {
            padding: 6px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            margin-top: 10px;
            box-shadow: 0 0 8px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #007BFF;
            color: white;
        }
        img {
            max-width: 100px;
            height: auto;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .actions form {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .status-pending { color: #FF8800; font-weight: bold; }
        .status-approved { color: green; font-weight: bold; }
        .status-rejected { color: red; font-weight: bold; }
    </style>
</head>
<body>

<h2>Admin - Manage Claims</h2>
<a class="back-link" href="admin_dashboard.php">← Back to Dashboard</a>

<form method="GET" class="filter-form">
    <select name="status">
        <option value="">All Statuses</option>
        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
    </select>

    <input type="text" name="search" placeholder="Search by user/receipt" value="<?= htmlspecialchars($search_query) ?>">

    <button type="submit">Filter</button>
</form>

<table>
    <thead>
        <tr>
            <th>Image</th>
            <th>Title</th>
            <th>Description</th>
            <th>Type</th>
            <th>Location</th>
            <th>Posted On</th>
            <th>User</th>
            <th>Receipt</th>
            <th>Post Type</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($result->num_rows === 0): ?>
        <tr><td colspan="11" style="text-align:center;">No claims found.</td></tr>
    <?php else: ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['image_path'] ? "<img src='{$row['image_path']}' alt='Image'>" : 'No image'; ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= htmlspecialchars($row['type']) ?></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
                <td><?= htmlspecialchars($row['user_email']) ?></td>
                <td><strong><?= htmlspecialchars($row['receipt_code']) ?></strong></td>
                <td><?= ucfirst($row['post_type']) ?></td>
                <td><span class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                <td class="actions">
                    <?php if ($row['status'] === 'pending'): ?>
                        <form method="POST">
                            <input type="hidden" name="claim_id" value="<?= $row['claim_id'] ?>">
                            <select name="status" required>
                                <option value="">-- Select --</option>
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                            <button type="submit">Update</button>
                        </form>
                    <?php else: ?>
                        <em>No Action</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php endif; ?>
    </tbody>
</table>

<?php $conn->close(); ?>

</body>
</html>
