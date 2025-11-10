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
    $user_name = htmlspecialchars(trim($_POST['review_name']));
    $rating = (int)$_POST['rating'];
    $comment = htmlspecialchars(trim($_POST['comment']));
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    if ($rating >= 1 && $rating <= 5 && !empty($user_name) && !empty($comment)) {
        $review_insert = $conn->prepare("INSERT INTO reviews (destination_id, user_id, user_name, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $review_insert->bind_param("iisis", $id, $user_id, $user_name, $rating, $comment);
        if ($review_insert->execute()) {
            header("Location: destination.php?id=$id&success=1");
            exit();
        }
    }
}

$image = !empty($destination['image_path']) ? UPLOAD_URL . $destination['image_path'] : 'https://via.placeholder.com/800x400?text=' . urlencode($destination['name']);
$current_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($destination['name']); ?> - Tourism Guide</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($destination['description'], 0, 150)); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Lightbox CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    
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
        .review-card {
            border-left: 4px solid #28a745;
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .review-rating {
            color: #ffc107;
            font-size: 1rem;
        }
        .star-rating {
            display: flex;
            gap: 5px;
            font-size: 2rem;
            cursor: pointer;
        }
        .star-rating i {
            color: #ddd;
            transition: color 0.2s;
        }
        .star-rating i.active,
        .star-rating i:hover,
        .star-rating i:hover ~ i {
            color: #ffc107;
        }
        
        /* Share buttons */
        .share-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .share-btn {
            flex: 1;
            text-align: center;
        }
        
        /* Back to top */
        #backToTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #132365ff 0%, #4b59a3ff 100%);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        #backToTop.show {
            opacity: 1;
            visibility: visible;
        }
        
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
    <!-- Back to Top Button -->
    <button id="backToTop" title="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

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

    <!-- Success Alert -->
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
        <i class="fas fa-check-circle"></i> Thank you! Your review has been submitted successfully and is awaiting approval.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <div class="hero-image" style="background-image: url('<?php echo $image; ?>');">
        <div class="hero-overlay">
            <div class="container">
                <span class="badge bg-primary mb-2">
                    <i class="fas <?php echo htmlspecialchars($destination['icon']); ?>"></i> <?php echo htmlspecialchars($destination['category_name']); ?>
                </span>
                <h1 class="display-4 fw-bold"><?php echo htmlspecialchars($destination['name']); ?></h1>
                <?php if ($rating_data['count'] > 0): ?>
                    <div class="rating mb-2">
                        <?php 
                        $avg_rating = round($rating_data['avg_rating']);
                        for($i=0; $i<5; $i++): ?>
                            <i class="fas fa-star<?php echo $i < $avg_rating ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                        <span class="ms-2"><?php echo number_format($rating_data['avg_rating'], 1); ?> / 5</span>
                        <span class="ms-2">(<?php echo $rating_data['count']; ?> reviews)</span>
                    </div>
                <?php endif; ?>
                <p class="lead"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($destination['address']); ?></p>
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
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($destination['description'])); ?></p>
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
                                        <a href="<?php echo UPLOAD_URL . htmlspecialchars($img['image_path']); ?>" 
                                           data-lightbox="gallery" 
                                           data-title="<?php echo htmlspecialchars($img['caption']); ?>">
                                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($img['image_path']); ?>" 
                                                 class="img-fluid gallery-img" 
                                                 alt="<?php echo htmlspecialchars($img['caption']); ?>">
                                        </a>
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

                <!-- Reviews Section -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-comments"></i> Reviews & Ratings</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($reviews->num_rows > 0): ?>
                            <h5 class="mb-3">What visitors are saying:</h5>
                            <?php while ($review = $reviews->fetch_assoc()): ?>
                                <div class="review-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($review['user_name']); ?></h6>
                                            <div class="review-rating">
                                                <?php for($i=0; $i<5; $i++): ?>
                                                    <i class="fas fa-star<?php echo $i < $review['rating'] ? '' : '-o'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No reviews yet. Be the first to share your experience!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Write Review Section -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-pen"></i> Write a Review</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="review_name" class="form-label">Your Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="review_name" name="review_name" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Rating <span class="text-danger">*</span></label>
                                <div class="star-rating" id="starRating">
                                    <i class="far fa-star" data-rating="1"></i>
                                    <i class="far fa-star" data-rating="2"></i>
                                    <i class="far fa-star" data-rating="3"></i>
                                    <i class="far fa-star" data-rating="4"></i>
                                    <i class="far fa-star" data-rating="5"></i>
                                </div>
                                <input type="hidden" id="rating" name="rating" required>
                                <small class="text-muted">Click on the stars to rate</small>
                            </div>

                            <div class="mb-3">
                                <label for="comment" class="form-label">Your Review <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="comment" name="comment" rows="4" 
                                          placeholder="Share your experience about this place..." required></textarea>
                            </div>

                            <button type="submit" name="submit_review" class="btn btn-success">
                                <i class="fas fa-paper-plane"></i> Submit Review
                            </button>
                        </form>
                    </div>
                </div>
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
                                <p class="mb-0"><?php echo htmlspecialchars($destination['opening_hours']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($destination['entry_fee']): ?>
                            <div class="info-card">
                                <h6><i class="fas fa-ticket-alt"></i> Entry Fee</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($destination['entry_fee']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($destination['contact_number']): ?>
                            <div class="info-card">
                                <h6><i class="fas fa-phone"></i> Contact</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($destination['contact_number']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($destination['email']): ?>
                            <div class="info-card">
                                <h6><i class="fas fa-envelope"></i> Email</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($destination['email']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($destination['website']): ?>
                            <div class="info-card">
                                <h6><i class="fas fa-globe"></i> Website</h6>
                                <p class="mb-0">
                                    <a href="<?php echo htmlspecialchars($destination['website']); ?>" target="_blank">Visit Website</a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Share This -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-share-alt"></i> Share This Place</h5>
                    </div>
                    <div class="card-body">
                        <div class="share-buttons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_url); ?>" 
                               target="_blank" class="btn btn-primary share-btn">
                                <i class="fab fa-facebook"></i> Facebook
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($current_url); ?>&text=<?php echo urlencode($destination['name']); ?>" 
                               target="_blank" class="btn btn-info share-btn">
                                <i class="fab fa-twitter"></i> Twitter
                            </a>
                        </div>
                        <button class="btn btn-secondary w-100 mt-2" onclick="copyLink()">
                            <i class="fas fa-link"></i> Copy Link
                        </button>
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

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4">
        <p>&copy; 2025 Tourism Guide System. All rights reserved.</p>
        <p><small>Powered by OpenStreetMap & Leaflet (100% Free)</small></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    
    <script>
        // Star Rating System
        const stars = document.querySelectorAll('#starRating i');
        const ratingInput = document.getElementById('rating');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingInput.value = rating;
                
                // Update star display
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas', 'active');
                    } else {
                        s.classList.remove('fas', 'active');
                        s.classList.add('far');
                    }
                });
            });
            
            // Hover effect
            star.addEventListener('mouseenter', function() {
                const rating = this.getAttribute('data-rating');
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
        
        // Reset on mouse leave
        document.getElementById('starRating').addEventListener('mouseleave', function() {
            const currentRating = ratingInput.value;
            stars.forEach((s, index) => {
                if (currentRating && index < currentRating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });

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
        
        // Copy link function
        function copyLink() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copied to clipboard!');
            }).catch(() => {
                alert('Failed to copy link');
            });
        }
        
        // Back to Top Button
        const backToTopBtn = document.getElementById('backToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });
        
        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Lightbox configuration
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': "Image %1 of %2"
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>