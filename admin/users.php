<?php
require_once '../config/database.php';
requireAdmin();

$conn = getDBConnection();
$error = '';
$success = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id = $id");
        $success = "User deleted successfully!";
    } else {
        $error = "You cannot delete your own account!";
    }
}

// Handle role change
if (isset($_GET['toggle_role'])) {
    $id = (int)$_GET['toggle_role'];
    if ($id != $_SESSION['user_id']) {
        $conn->query("UPDATE users SET role = IF(role='admin', 'user', 'admin') WHERE id = $id");
        $success = "User role updated successfully!";
    } else {
        $error = "You cannot change your own role!";
    }
}

// Fetch all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Tourism Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            width: 250px;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 20px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4">
            <h4><i class="fas fa-map-marked-alt"></i> Tourism Admin</h4>
            <hr class="bg-white">
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a class="nav-link" href="destinations.php">
                <i class="fas fa-map-pin"></i> Destinations
            </a>
            <a class="nav-link" href="categories.php">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a class="nav-link" href="routes.php">
                <i class="fas fa-route"></i> Routes
            </a>
            <a class="nav-link active" href="reviews.php">
                <i class="fas fa-star"></i> Reviews & Feedback
                <?php if ($unread_feedback > 0): ?>
                    <span class="badge bg-danger"><?php echo $unread_feedback; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link active" href="users.php">
                <i class="fas fa-users"></i> Users
            </a>
            <hr class="bg-white">
            <a class="nav-link" href="../index.php">
                <i class="fas fa-globe"></i> View Site
            </a>
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2 class="mb-4"><i class="fas fa-users"></i> Manage Users</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list"></i> All Users</h5>
                <small>Total: <?php echo $users->num_rows; ?> users</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <strong><?php echo $user['username']; ?></strong>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-info ms-2">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $user['email'] ?: '<span class="text-muted">No email</span>'; ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-shield-alt"></i> Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-user"></i> User
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="?toggle_role=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-warning" 
                                               title="Toggle Role"
                                               onclick="return confirm('Change user role?')">
                                                <i class="fas fa-exchange-alt"></i>
                                            </a>
                                            <a href="?delete=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               title="Delete User"
                                               onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fas fa-lock"></i> Protected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-4">
            <h5><i class="fas fa-info-circle"></i> User Management Info</h5>
            <ul>
                <li>New users can register from the login page</li>
                <li>Toggle role to change between Admin and User</li>
                <li>You cannot delete or modify your own account from this page</li>
                <li>Admins have access to the admin panel and can manage content</li>
                <li>Regular users can only view the public site</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>