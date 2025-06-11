<?php
session_start();
require_once 'connect.php';

// Ensure admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch users
$sql = "SELECT id, name, username, user_email, role, created_at FROM users ORDER BY id DESC";
$result = $conn->query($sql);

$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: url('rmmc.png') no-repeat center center fixed;
            background-size: cover;
            backdrop-filter: blur(10px);
        }

        .container {
            max-width: 1000px;
            margin: 50px auto;
            background: rgba(255, 255, 255, 0.75);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #0930b8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #0a3cb1;
            color: white;
        }

        tr:hover {
            background-color: #f2f2f2;
        }

        .actions a {
            margin-right: 10px;
            text-decoration: none;
            color: #0056b3;
            font-weight: 600;
        }

        .actions a:hover {
            text-decoration: underline;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #0056b3;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Manage Users</h2>

    <?php if (empty($users)): ?>
        <p>No users found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['user_email']) ?></td>
                        <td><?= htmlspecialchars($row['role']) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td class="actions">
                            <a href="edit_user.php?id=<?= $row['id'] ?>">Edit</a>
                            <a href="delete_user.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="back-link">
        <a href="admin_dashboard.php">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>
