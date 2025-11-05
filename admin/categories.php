<?php
require_once '../config/database.php';
requireAdmin();

$conn = getDBConnection();
$error = '';
$success = '';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $icon = sanitizeInput($_POST['icon']);
    
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        // Update
        $id = (int)$_POST['edit_id'];
        $stmt = $conn->prepare("UPDATE categories SET name = ?, icon = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $icon, $id);
        if ($stmt->execute()) {
            $success = "Category updated successfully!";
        }
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $icon);
        if ($stmt->execute()) {
            $success = "Category added successfully!";
        }
    }
}

// Fetch unread feedback count
$unread_feedback = $conn->query("SELECT COUNT(*) as count FROM website_feedback WHERE is_read = 0")->fetch_assoc()['count'];

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM categories WHERE id = $id");
    $success = "Category deleted successfully!";
}

// Fetch categories
$categories = $conn->query("SELECT c.*, COUNT(d.id) as destination_count 
                            FROM categories c 
                            LEFT JOIN destinations d ON c.id = d.category_id 
                            GROUP BY c.id 
                            ORDER BY c.name");

// Font Awesome icons for categories
$icon_options = [
    'fa-camera' => 'Camera',
    'fa-utensils' => 'Restaurant',
    'fa-bed' => 'Hotel',
    'fa-bus' => 'Transport',
    'fa-landmark' => 'Landmark',
    'fa-tree' => 'Nature',
    'fa-shopping-bag' => 'Shopping',
    'fa-film' => 'Entertainment',
    'fa-church' => 'Church',
    'fa-building' => 'Building',
    'fa-monument' => 'Monument',
    'fa-water' => 'Beach',
    'fa-mountain' => 'Mountain',
    'fa-coffee' => 'Cafe',
    'fa-music' => 'Music',
    'fa-palette' => 'Art',
    'fa-heart' => 'Favorite'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Tourism Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #2e50e8ff 0%, #8017e8ff 100%);
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
        .icon-preview {
            font-size: 2rem;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            display: inline-block;
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
            <a class="nav-link active" href="categories.php">
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
        <h2 class="mb-4"><i class="fas fa-tags"></i> Manage Categories</h2>

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

        <div class="row">
            <!-- Add/Edit Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0" id="formTitle"><i class="fas fa-plus"></i> Add Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="categoryForm">
                            <input type="hidden" name="edit_id" id="editId">
                            
                            <div class="mb-3">
                                <label class="form-label">Category Name *</label>
                                <input type="text" class="form-control" name="name" id="categoryName" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Icon *</label>
                                <select class="form-select" name="icon" id="categoryIcon" required onchange="updateIconPreview()">
                                    <option value="">Select Icon</option>
                                    <?php foreach ($icon_options as $icon => $label): ?>
                                        <option value="<?php echo $icon; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="text-center mt-3">
                                    <div class="icon-preview" id="iconPreview">
                                        <i class="fas fa-question"></i>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> <span id="submitText">Save Category</span>
                            </button>
                            <button type="button" class="btn btn-secondary w-100 mt-2" onclick="resetForm()" id="cancelBtn" style="display:none;">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Categories List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> All Categories</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Icon</th>
                                        <th>Name</th>
                                        <th>Destinations</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="icon-preview" style="font-size: 1.5rem;">
                                                    <i class="fas <?php echo $cat['icon']; ?>"></i>
                                                </div>
                                            </td>
                                            <td><strong><?php echo $cat['name']; ?></strong></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $cat['destination_count']; ?> destinations</span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>', '<?php echo $cat['icon']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($cat['destination_count'] == 0): ?>
                                                    <a href="?delete=<?php echo $cat['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Delete this category?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateIconPreview() {
            const icon = document.getElementById('categoryIcon').value;
            const preview = document.getElementById('iconPreview');
            if (icon) {
                preview.innerHTML = `<i class="fas ${icon}"></i>`;
            } else {
                preview.innerHTML = '<i class="fas fa-question"></i>';
            }
        }

        function editCategory(id, name, icon) {
            document.getElementById('editId').value = id;
            document.getElementById('categoryName').value = name;
            document.getElementById('categoryIcon').value = icon;
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Category';
            document.getElementById('submitText').textContent = 'Update Category';
            document.getElementById('cancelBtn').style.display = 'block';
            updateIconPreview();
        }

        function resetForm() {
            document.getElementById('categoryForm').reset();
            document.getElementById('editId').value = '';
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus"></i> Add Category';
            document.getElementById('submitText').textContent = 'Save Category';
            document.getElementById('cancelBtn').style.display = 'none';
            updateIconPreview();
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>