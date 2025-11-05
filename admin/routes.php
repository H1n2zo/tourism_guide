<?php
require_once '../config/database.php';
requireAdmin();

$conn = getDBConnection();
$error = '';
$success = '';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $route_name = sanitizeInput($_POST['route_name']);
    $origin_id = (int)$_POST['origin_id'];
    $destination_id = (int)$_POST['destination_id'];
    $transport_mode = sanitizeInput($_POST['transport_mode']);
    $distance_km = (float)$_POST['distance_km'];
    $estimated_time_minutes = (int)$_POST['estimated_time_minutes'];
    $base_fare = (float)$_POST['base_fare'];
    $fare_per_km = (float)$_POST['fare_per_km'];
    $description = sanitizeInput($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        // Update
        $id = (int)$_POST['edit_id'];
        $stmt = $conn->prepare("UPDATE routes SET route_name = ?, origin_id = ?, destination_id = ?, 
                                transport_mode = ?, distance_km = ?, estimated_time_minutes = ?, 
                                base_fare = ?, fare_per_km = ?, description = ?, is_active = ? 
                                WHERE id = ?");
        $stmt->bind_param("siisddiidii", $route_name, $origin_id, $destination_id, $transport_mode,
                         $distance_km, $estimated_time_minutes, $base_fare, $fare_per_km, 
                         $description, $is_active, $id);
        if ($stmt->execute()) {
            $success = "Route updated successfully!";
        }
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO routes (route_name, origin_id, destination_id, transport_mode, 
                                distance_km, estimated_time_minutes, base_fare, fare_per_km, description, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisdiidis", $route_name, $origin_id, $destination_id, $transport_mode,
                         $distance_km, $estimated_time_minutes, $base_fare, $fare_per_km, 
                         $description, $is_active);
        if ($stmt->execute()) {
            $success = "Route added successfully!";
        }
    }
}

// Fetch unread feedback count
$unread_feedback = $conn->query("SELECT COUNT(*) as count FROM website_feedback WHERE is_read = 0")->fetch_assoc()['count'];

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM routes WHERE id = $id");
    $success = "Route deleted successfully!";
}

// Fetch routes
$routes = $conn->query("SELECT r.*, o.name as origin_name, d.name as destination_name 
                        FROM routes r
                        LEFT JOIN destinations o ON r.origin_id = o.id
                        LEFT JOIN destinations d ON r.destination_id = d.id
                        ORDER BY r.created_at DESC");

// Fetch destinations for dropdown
$destinations = $conn->query("SELECT id, name FROM destinations WHERE is_active = 1 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routes - Tourism Admin</title>
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
            <a class="nav-link active" href="routes.php">
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
        <h2 class="mb-4"><i class="fas fa-route"></i> Manage Routes</h2>

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

        <!-- Add/Edit Form -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0" id="formTitle"><i class="fas fa-plus"></i> Add New Route</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="routeForm">
                    <input type="hidden" name="edit_id" id="editId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Route Name</label>
                            <input type="text" class="form-control" name="route_name" id="routeName" 
                                   placeholder="e.g., SM to Magellan's Cross">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transport Mode *</label>
                            <select class="form-select" name="transport_mode" id="transportMode" required>
                                <option value="">Select Mode</option>
                                <option value="jeepney">Jeepney</option>
                                <option value="taxi">Taxi</option>
                                <option value="bus">Bus</option>
                                <option value="van">Van</option>
                                <option value="tricycle">Tricycle</option>
                                <option value="walking">Walking</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Origin *</label>
                            <select class="form-select" name="origin_id" id="originId" required>
                                <option value="">Select Origin</option>
                                <?php 
                                $destinations->data_seek(0);
                                while ($dest = $destinations->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $dest['id']; ?>"><?php echo $dest['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Destination *</label>
                            <select class="form-select" name="destination_id" id="destinationId" required>
                                <option value="">Select Destination</option>
                                <?php 
                                $destinations->data_seek(0);
                                while ($dest = $destinations->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $dest['id']; ?>"><?php echo $dest['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Distance (km)</label>
                            <input type="number" class="form-control" name="distance_km" id="distanceKm" step="0.1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Est. Time (min)</label>
                            <input type="number" class="form-control" name="estimated_time_minutes" id="estimatedTime">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Base Fare (PHP)</label>
                            <input type="number" class="form-control" name="base_fare" id="baseFare" step="0.01">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fare per km (PHP)</label>
                            <input type="number" class="form-control" name="fare_per_km" id="farePerKm" step="0.01">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="description" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <span id="submitText">Save Route</span>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()" id="cancelBtn" style="display:none;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </form>
            </div>
        </div>

        <!-- Routes List -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> All Routes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Route</th>
                                <th>Transport</th>
                                <th>Distance</th>
                                <th>Time</th>
                                <th>Fare</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($route = $routes->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $route['route_name'] ?: 'Unnamed Route'; ?></strong><br>
                                        <small class="text-muted">
                                            <?php echo $route['origin_name']; ?> → <?php echo $route['destination_name']; ?>
                                        </small>
                                    </td>
                                    <td><span class="badge bg-info"><?php echo ucfirst($route['transport_mode']); ?></span></td>
                                    <td><?php echo $route['distance_km']; ?> km</td>
                                    <td><?php echo $route['estimated_time_minutes']; ?> min</td>
                                    <td>PHP <?php echo number_format($route['base_fare'] + ($route['distance_km'] * $route['fare_per_km']), 2); ?></td>
                                    <td>
                                        <?php if ($route['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick='editRoute(<?php echo json_encode($route); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $route['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this route?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editRoute(route) {
            document.getElementById('editId').value = route.id;
            document.getElementById('routeName').value = route.route_name || '';
            document.getElementById('transportMode').value = route.transport_mode;
            document.getElementById('originId').value = route.origin_id;
            document.getElementById('destinationId').value = route.destination_id;
            document.getElementById('distanceKm').value = route.distance_km;
            document.getElementById('estimatedTime').value = route.estimated_time_minutes;
            document.getElementById('baseFare').value = route.base_fare;
            document.getElementById('farePerKm').value = route.fare_per_km;
            document.getElementById('description').value = route.description || '';
            document.getElementById('isActive').checked = route.is_active == 1;
            
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Route';
            document.getElementById('submitText').textContent = 'Update Route';
            document.getElementById('cancelBtn').style.display = 'inline-block';
            
            window.scrollTo({top: 0, behavior: 'smooth'});
        }

        function resetForm() {
            document.getElementById('routeForm').reset();
            document.getElementById('editId').value = '';
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus"></i> Add New Route';
            document.getElementById('submitText').textContent = 'Save Route';
            document.getElementById('cancelBtn').style.display = 'none';
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>