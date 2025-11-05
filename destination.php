<?php
require_once 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT d.*, c.name as category_name, c.icon 
                        FROM destinations d 
                        LEFT JOIN categories c ON d.category_id = c.id 
                        WHERE d.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$destination = $result->fetch_assoc();

// Fetch additional images
$images_stmt = $conn->prepare("SELECT * FROM destination_images WHERE destination_id = ? ORDER BY is_primary DESC");
$images_stmt->bind_param("i", $id);
$images_stmt->execute();
$images = $images_stmt->get_result();

// Fetch reviews
$reviews_stmt = $conn->prepare("SELECT * FROM reviews WHERE destination_id = ? AND is_approved = 1 ORDER BY created_at DESC");
$reviews_stmt->bind_param("i", $id);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result();

// Calculate average rating
$rating_stmt = $conn->prepare("SELECT COUNT(*) as count, AVG(rating) as avg_rating FROM reviews WHERE destination_id = ? AND is_approved = 1");
$rating_stmt->bind_param("i", $id);
$rating_stmt->execute();
$rating_data = $rating_stmt->get_result()->fetch_assoc();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $user_name = sanitizeInput($_POST['review_name']);
    $rating = (int)$_POST['rating'];
    $comment = sanitizeInput($_POST['comment']);
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
    
    if ($rating >= 1 && $rating <= 5 && !empty($user_name)) {
        $review_insert = $conn->prepare("INSERT INTO reviews (destination_id, user_id, user_name, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $review_insert->bind_param("iisis", $id, $user_id, $user_name, $rating, $comment);
        if ($review_insert->execute()) {
            header("Location: destination.php?id=$id&success=1");
            exit();
        }
    }
}

$image = !empty($destination['image_path']) ? UPLOAD_URL . $destination['image_path'] : 'https://via.placeholder.com/800x400?text=' . urlencode($destination['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $destination['name']; ?> - Tourism Guide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        .hero-image {
            height: 500px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .hero-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
            padding: 40px;
        }
        .info-card {
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
        }
        .rating {
            color: #ffc107;
            font-size: 1.2rem;
        }
        #map {
            height: 400px;
            border-radius: 10px;
        }
        .gallery-img {
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .gallery-img:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-map-marked-alt"></i> Tourism Guide
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#destinations"><i class="fas fa-map-pin"></i> Destinations</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-image" style="background-image: url('<?php echo $image; ?>');">
        <div class="hero-overlay">
            <div class="container">
                <span class="badge bg-primary mb-2">
                    <i class="fas <?php echo $destination['icon']; ?>"></i> <?php echo $destination['category_name']; ?>
                </span>
                <h1 class="display-4 fw-bold"><?php echo $destination['name']; ?></h1>
                <?php if ($destination['rating'] > 0): ?>
                    <div class="rating mb-2">
                        <?php for($i=0; $i<5; $i++): ?>
                            <i class="fas fa-star<?php echo $i < $destination['rating'] ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                        <span class="ms-2"><?php echo $destination['rating']; ?> / 5</span>
                    </div>
                <?php endif; ?>
                <p class="lead"><i class="fas fa-map-marker-alt"></i> <?php echo $destination['address']; ?></p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8">
                <!-- Description -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> About This Place</h3>
                        <p class="card-text"><?php echo nl2br($destination['description']); ?></p>
                    </div>
                </div>

                <!-- Gallery -->
                <?php if ($images->num_rows > 0): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="card-title"><i class="fas fa-images"></i> Photo Gallery</h3>
                            <div class="row g-2">
                                <?php while ($img = $images->fetch_assoc()): ?>
                                    <div class="col-md-4">
                                        <img src="<?php echo UPLOAD_URL . $img['image_path']; ?>" 
                                             class="img-fluid gallery-img" 
                                             alt="<?php echo $img['caption']; ?>"
                                             data-bs-toggle="modal" 
                                             data-bs-target="#imageModal"
                                             onclick="showImage('<?php echo UPLOAD_URL . $img['image_path']; ?>')">
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Map -->
                <?php if ($destination['latitude'] && $destination['longitude']): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="card-title"><i class="fas fa-map"></i> Location</h3>
                            <div id="map"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Info -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Quick Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($destination['opening_hours']): ?>
                            <div class="info-card">
                                <h6><i class="fas fa-clock"></i> Opening Hours</h6>
                                <p class="mb-0"><?php echo $destination['opening_hours']; ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($destination['entry_fee']): ?>
                            <div class="info-card">
                                <h6><i class="fas fa-ticket-alt"></i> Entry Fee</h6>
                                <p class="mb-0"><?php echo $destination['entry_fee']; ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($destination['contact_number']): ?>
                            <div class="info-card">
                                <h6><i class="fas fa-phone"></i> Contact</h6>
                                <p class="mb-0"><?php echo $destination['contact_number']; ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($destination['email']): ?>
                            <div class="info-card">
                                <h6><i class="fas fa-envelope"></i> Email</h6>
                                <p class="mb-0"><?php echo $destination['email']; ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($destination['website']): ?>
                            <div class="info-card">
                                <h6><i class="fas fa-globe"></i> Website</h6>
                                <p class="mb-0">
                                    <a href="<?php echo $destination['website']; ?>" target="_blank">Visit Website</a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <h5><i class="fas fa-map-marked-alt"></i> Plan Your Visit</h5>
                        <a href="index.php#routes" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-route"></i> Find Route Here
                        </a>
                        <a href="index.php#destinations" class="btn btn-outline-primary w-100">
                            <i class="fas fa-compass"></i> Explore More Places
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <img id="modalImage" src="" class="img-fluid w-100">
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4">
        <p>&copy; 2025 Tourism Guide System. All rights reserved.</p>
        <p><small>Powered by OpenStreetMap & Leaflet (100% Free)</small></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        function showImage(src) {
            document.getElementById('modalImage').src = src;
        }

        // Initialize Leaflet map
        <?php if ($destination['latitude'] && $destination['longitude']): ?>
        const map = L.map('map').setView([<?php echo $destination['latitude']; ?>, <?php echo $destination['longitude']; ?>], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        
        const marker = L.marker([<?php echo $destination['latitude']; ?>, <?php echo $destination['longitude']; ?>])
            .addTo(map)
            .bindPopup('<h6><?php echo addslashes($destination['name']); ?></h6><p><?php echo addslashes($destination['address']); ?></p>')
            .openPopup();
        <?php endif; ?>
    </script>
</body>
</html>
<?php $conn->close(); ?>