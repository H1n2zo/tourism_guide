<?php
require_once '../config/database.php';
requireAdmin();

$conn = getDBConnection();
$error = '';
$success = '';

// Check if editing
$edit_mode = isset($_GET['id']);
$destination = null;

if ($edit_mode) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: destinations.php');
        exit();
    }
    $destination = $result->fetch_assoc();
}

// Handle photo deletion
if (isset($_GET['delete_photo'])) {
    $photo_id = (int)$_GET['delete_photo'];
    $dest_id = (int)$_GET['id'];
    
    $photo_stmt = $conn->prepare("SELECT image_path FROM destination_images WHERE id = ?");
    $photo_stmt->bind_param("i", $photo_id);
    $photo_stmt->execute();
    $photo_result = $photo_stmt->get_result();
    
    if ($photo_data = $photo_result->fetch_assoc()) {
        deleteImage($photo_data['image_path']);
        $conn->query("DELETE FROM destination_images WHERE id = $photo_id");
        $success = "Photo deleted successfully!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = sanitizeInput($_POST['description']);
    $address = sanitizeInput($_POST['address']);
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $contact_number = sanitizeInput($_POST['contact_number']);
    $email = sanitizeInput($_POST['email']);
    $website = sanitizeInput($_POST['website']);
    $opening_hours = sanitizeInput($_POST['opening_hours']);
    $entry_fee = sanitizeInput($_POST['entry_fee']);
    $rating = !empty($_POST['rating']) ? (float)$_POST['rating'] : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $image_path = $edit_mode ? $destination['image_path'] : '';
    
    // Handle featured image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        if ($edit_mode && !empty($destination['image_path'])) {
            deleteImage($destination['image_path']);
        }
        
        $upload_result = uploadImage($_FILES['image'], 'destinations');
        if ($upload_result['success']) {
            $image_path = $upload_result['path'];
        } else {
            $error = $upload_result['message'];
        }
    }
    
    if (empty($error)) {
        if ($edit_mode) {
            $stmt = $conn->prepare("UPDATE destinations SET 
                name = ?, category_id = ?, description = ?, address = ?,
                latitude = ?, longitude = ?, contact_number = ?, email = ?,
                website = ?, opening_hours = ?, entry_fee = ?, rating = ?,
                image_path = ?, is_active = ?
                WHERE id = ?");
            $stmt->bind_param("sissddsssssdsii", 
                $name, $category_id, $description, $address,
                $latitude, $longitude, $contact_number, $email,
                $website, $opening_hours, $entry_fee, $rating,
                $image_path, $is_active, $id
            );
        } else {
            $stmt = $conn->prepare("INSERT INTO destinations 
                (name, category_id, description, address, latitude, longitude,
                contact_number, email, website, opening_hours, entry_fee, rating,
                image_path, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissddsssssdsii",
                $name, $category_id, $description, $address,
                $latitude, $longitude, $contact_number, $email,
                $website, $opening_hours, $entry_fee, $rating,
                $image_path, $is_active
            );
        }
        
        if ($stmt->execute()) {
            if (!$edit_mode) {
                $id = $conn->insert_id;
            }
            
            // Handle additional photos upload
            if (isset($_FILES['additional_photos']) && !empty($_FILES['additional_photos']['name'][0])) {
                $photo_count = count($_FILES['additional_photos']['name']);
                
                for ($i = 0; $i < $photo_count; $i++) {
                    if ($_FILES['additional_photos']['error'][$i] === 0) {
                        $photo_file = [
                            'name' => $_FILES['additional_photos']['name'][$i],
                            'type' => $_FILES['additional_photos']['type'][$i],
                            'tmp_name' => $_FILES['additional_photos']['tmp_name'][$i],
                            'error' => $_FILES['additional_photos']['error'][$i],
                            'size' => $_FILES['additional_photos']['size'][$i]
                        ];
                        
                        $upload_result = uploadImage($photo_file, 'destinations');
                        if ($upload_result['success']) {
                            $caption = isset($_POST['photo_captions'][$i]) ? sanitizeInput($_POST['photo_captions'][$i]) : '';
                            $photo_stmt = $conn->prepare("INSERT INTO destination_images (destination_id, image_path, caption) VALUES (?, ?, ?)");
                            $photo_stmt->bind_param("iss", $id, $upload_result['path'], $caption);
                            $photo_stmt->execute();
                        }
                    }
                }
            }
            
            $success = $edit_mode ? "Destination updated successfully!" : "Destination added successfully!";
            if (!$edit_mode) {
                header('Location: destinations.php');
                exit();
            }
        } else {
            $error = "Database error: " . $stmt->error;
        }
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Fetch existing additional photos if editing
$existing_photos = [];
if ($edit_mode) {
    $photos_stmt = $conn->prepare("SELECT * FROM destination_images WHERE destination_id = ? ORDER BY id");
    $photos_stmt->bind_param("i", $id);
    $photos_stmt->execute();
    $existing_photos = $photos_stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit' : 'Add'; ?> Destination - Tourism Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
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
        .image-preview {
            max-width: 300px;
            margin-top: 10px;
            border-radius: 5px;
        }
        #map {
            height: 400px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .photo-item {
            position: relative;
            display: inline-block;
            margin: 10px;
        }
        .photo-item img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
        }
        .photo-delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
        }
        .photo-delete-btn:hover {
            background: rgb(220, 53, 69);
        }
        .additional-photo-preview {
            display: inline-block;
            margin: 10px;
        }
        .additional-photo-preview img {
            width: 100px;
            height: 100px;
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
            <h2>
                <i class="fas fa-<?php echo $edit_mode ? 'edit' : 'plus'; ?>"></i>
                <?php echo $edit_mode ? 'Edit' : 'Add New'; ?> Destination
            </h2>
            <a href="destinations.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

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

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Basic Information -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Destination Name *</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo $destination['name'] ?? ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category *</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo ($destination['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo $cat['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="5"><?php echo $destination['description'] ?? ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"><?php echo $destination['address'] ?? ''; ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Rating (0-5)</label>
                                    <input type="number" class="form-control" name="rating" 
                                           min="0" max="5" step="0.1" 
                                           value="<?php echo $destination['rating'] ?? '0'; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" 
                                               <?php echo ($destination['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Location Coordinates (Ormoc City)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Latitude</label>
                                    <input type="number" class="form-control" name="latitude" 
                                           id="latitude" step="any" 
                                           value="<?php echo $destination['latitude'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Longitude</label>
                                    <input type="number" class="form-control" name="longitude" 
                                           id="longitude" step="any" 
                                           value="<?php echo $destination['longitude'] ?? ''; ?>">
                                </div>
                            </div>
                            <button type="button" class="btn btn-info btn-sm" onclick="getCurrentLocation()">
                                <i class="fas fa-crosshairs"></i> Get Current Location
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="showMapPicker()">
                                <i class="fas fa-map"></i> Pick on Map
                            </button>
                            <div id="map" style="display: none;"></div>
                        </div>
                    </div>

                    <!-- Additional Photos -->
                    <?php if ($edit_mode && $existing_photos->num_rows > 0): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-images"></i> Current Gallery Photos</h5>
                        </div>
                        <div class="card-body">
                            <?php while ($photo = $existing_photos->fetch_assoc()): ?>
                                <div class="photo-item">
                                    <img src="<?php echo UPLOAD_URL . $photo['image_path']; ?>" alt="Gallery photo">
                                    <button type="button" class="photo-delete-btn" 
                                            onclick="if(confirm('Delete this photo?')) window.location.href='?id=<?php echo $id; ?>&delete_photo=<?php echo $photo['id']; ?>'">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php if ($photo['caption']): ?>
                                        <div class="text-center mt-1">
                                            <small><?php echo $photo['caption']; ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-camera"></i> Add More Photos</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Upload Multiple Photos (Max 5MB each)</label>
                                <input type="file" class="form-control" name="additional_photos[]" 
                                       accept="image/*" multiple onchange="previewAdditionalPhotos(this)">
                                <small class="text-muted">You can select multiple images at once</small>
                            </div>
                            <div id="additionalPhotoPreviews"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Featured Image -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-image"></i> Featured Image</h5>
                        </div>
                        <div class="card-body">
                            <input type="file" class="form-control" name="image" 
                                   accept="image/*" onchange="previewImage(this)">
                            <small class="text-muted">Max 5MB. JPG, PNG, GIF, WEBP</small>
                            <?php if ($edit_mode && !empty($destination['image_path'])): ?>
                                <img src="<?php echo UPLOAD_URL . $destination['image_path']; ?>" 
                                     class="image-preview img-fluid" id="imagePreview">
                            <?php else: ?>
                                <img id="imagePreview" class="image-preview img-fluid" style="display:none;">
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-phone"></i> Contact Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" class="form-control" name="contact_number" 
                                       value="<?php echo $destination['contact_number'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo $destination['email'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" class="form-control" name="website" 
                                       value="<?php echo $destination['website'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-clock"></i> Additional Info</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Opening Hours</label>
                                <input type="text" class="form-control" name="opening_hours" 
                                       placeholder="e.g., 8:00 AM - 5:00 PM"
                                       value="<?php echo $destination['opening_hours'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Entry Fee</label>
                                <input type="text" class="form-control" name="entry_fee" 
                                       placeholder="e.g., PHP 50 or Free"
                                       value="<?php echo $destination['entry_fee'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> <?php echo $edit_mode ? 'Update' : 'Save'; ?> Destination
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewAdditionalPhotos(input) {
            const container = document.getElementById('additionalPhotoPreviews');
            container.innerHTML = '';
            
            if (input.files) {
                Array.from(input.files).forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'additional-photo-preview';
                        div.innerHTML = `
                            <img src="${e.target.result}" alt="Preview ${index + 1}">
                            <div class="mt-1">
                                <small>Photo ${index + 1}</small>
                            </div>
                        `;
                        container.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            }
        }

        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    alert('Location captured successfully!');
                });
            } else {
                alert('Geolocation is not supported by your browser');
            }
        }

        let map, marker;
        function showMapPicker() {
            const mapDiv = document.getElementById('map');
            mapDiv.style.display = 'block';
            
            // Ormoc City coordinates
            const lat = parseFloat(document.getElementById('latitude').value) || 11.0059;
            const lng = parseFloat(document.getElementById('longitude').value) || 124.6075;
            
            if (!map) {
                map = L.map('map').setView([lat, lng], 13);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19
                }).addTo(map);
                
                marker = L.marker([lat, lng], {draggable: true}).addTo(map);
                
                marker.on('dragend', function() {
                    const pos = marker.getLatLng();
                    document.getElementById('latitude').value = pos.lat;
                    document.getElementById('longitude').value = pos.lng;
                });
                
                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    document.getElementById('latitude').value = e.latlng.lat;
                    document.getElementById('longitude').value = e.latlng.lng;
                });
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>