<?php
require_once '../config/database.php';
requireAdmin();

$conn = getDBConnection();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get image path before deletion
    $img_query = $conn->query("SELECT image_path FROM destinations WHERE id = $id");
    if ($img_query && $img_data = $img_query->fetch_assoc()) {
        if ($img_data['image_path']) {
            deleteImage($img_data['image_path']);
        }
    }
    
    $conn->query("DELETE FROM destinations WHERE id = $id");
    $success = "Destination deleted successfully!";
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE destinations SET is_active = NOT is_active WHERE id = $id");
    $success = "Status updated successfully!";
}

// Fetch all destinations
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$query = "SELECT d.*, c.name as category_name 
          FROM destinations d 
          LEFT JOIN categories c ON d.category_id = c.id 
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (d.name LIKE '%$search%' OR d.description LIKE '%$search%')";
}
if ($category_filter > 0) {
    $query .= " AND d.category_id = $category_filter";
}
$query .= " ORDER BY d.created_at DESC";
// Fetch unread feedback count
$unread_feedback = $conn->query("SELECT COUNT(*) as count FROM website_feedback WHERE is_read = 0")->fetch_assoc()['count'];

$destinations = $conn->query($query);
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Destinations - Tourism Admin</title>
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
        .destination-img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
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
            <a class="nav-link active" href="destinations.php">
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
            <a class="nav-link" href="users.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-map-pin"></i> Manage Destinations</h2>
            <a href="add_destination.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Destination
            </a>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="search" placeholder="Search destinations..." value="<?php echo $search; ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="category">
                            <option value="0">All Categories</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo $cat['name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Destinations Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($destinations->num_rows > 0): ?>
                                <?php while ($dest = $destinations->fetch_assoc()): 
                                    $image = !empty($dest['image_path']) ? UPLOAD_URL . $dest['image_path'] : 'https://via.placeholder.com/80x60';
                                ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo $image; ?>" class="destination-img" alt="<?php echo $dest['name']; ?>">
                                        </td>
                                        <td>
                                            <strong><?php echo $dest['name']; ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $dest['category_name']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($dest['latitude'] && $dest['longitude']): ?>
                                                <small><?php echo number_format($dest['latitude'], 4); ?>, <?php echo number_format($dest['longitude'], 4); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">No coordinates</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($dest['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo date('M d, Y', strtotime($dest['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit_destination.php?id=<?php echo $dest['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="../destination.php?id=<?php echo $dest['id']; ?>" class="btn btn-sm btn-info" title="View" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?toggle=<?php echo $dest['id']; ?>" class="btn btn-sm btn-warning" title="Toggle Status" onclick="return confirm('Toggle status?')">
                                                    <i class="fas fa-toggle-on"></i>
                                                </a>
                                                <a href="?delete=<?php echo $dest['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this destination?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">No destinations found</p>
                                        <a href="add_destination.php" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Add Your First Destination
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>