<?php
require_once 'config/database.php';

$conn = getDBConnection();
$success = '';
$error = '';

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = sanitizeInput($_POST['user_name']);
    $email = sanitizeInput($_POST['email']);
    $rating = (int)$_POST['rating'];
    $category = sanitizeInput($_POST['category']);
    $feedback = sanitizeInput($_POST['feedback']);
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
    
    if ($rating >= 1 && $rating <= 5 && !empty($user_name) && !empty($feedback)) {
        $stmt = $conn->prepare("INSERT INTO website_feedback (user_id, user_name, email, rating, category, feedback) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississ", $user_id, $user_name, $email, $rating, $category, $feedback);
        
        if ($stmt->execute()) {
            $success = "Thank you for your feedback! We appreciate your input.";
        } else {
            $error = "Sorry, there was an error submitting your feedback. Please try again.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch public feedback
$public_feedback = $conn->query("SELECT * FROM website_feedback WHERE is_public = 1 ORDER BY created_at DESC LIMIT 10");

// Calculate average website rating
$avg_rating_query = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM website_feedback");
$avg_rating_data = $avg_rating_query->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Tourism Guide System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .navbar {
            background: linear-gradient(135deg, #132365ff 0%, #4b59a3ff 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .hero-section {
            background: linear-gradient(135deg, #132365ff 0%, #4b59a3ff 100%);
            color: white;
            padding: 60px 0 80px;
        }
        .feedback-card {
            transition: transform 0.3s;
            border-left: 4px solid #667eea;
        }
        .feedback-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .rating {
            color: #ffc107;
        }
        .star-rating {
            direction: rtl;
            display: inline-flex;
            font-size: 2.5rem;
        }
        .star-rating input[type="radio"] {
            display: none;
        }
        .star-rating label {
            color: #ddd;
            cursor: pointer;
            padding: 0 10px;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input[type="radio"]:checked ~ label {
            color: #ffc107;
        }
        .form-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-top: -50px;
            position: relative;
            z-index: 10;
            padding: 30px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
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
                    <li class="nav-item">
                        <a class="nav-link active" href="feedback.php"><i class="fas fa-comment"></i> Feedback</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if (isAdmin()): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Panel</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3">We Value Your Feedback</h1>
            <p class="lead">Help us improve the Ormoc Tourism Guide System</p>
            <?php if ($avg_rating_data['count'] > 0): ?>
                <div class="rating mt-3" style="font-size: 1.5rem;">
                    Overall Rating: 
                    <?php 
                    $avg = round($avg_rating_data['avg_rating'], 1);
                    for($i=0; $i<5; $i++): ?>
                        <i class="fas fa-star"></i>
                    <?php endfor; ?>
                    <span class="ms-2"><?php echo $avg; ?> / 5</span>
                    <span class="ms-2">(<?php echo $avg_rating_data['count']; ?> ratings)</span>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Feedback Form -->
    <div class="container">
        <div class="form-section">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <h3 class="mb-4"><i class="fas fa-star"></i> Rate Our Tourism Guide</h3>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Your Name *</label>
                        <input type="text" class="form-control" name="user_name" 
                               value="<?php echo isLoggedIn() ? $_SESSION['username'] : ''; ?>" 
                               <?php echo isLoggedIn() ? 'readonly' : ''; ?> required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email (Optional)</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Rate the Tourism Guide System *</label>
                    <div class="text-center">
                        <div class="star-rating">
                            <?php for($i=5; $i>=1; $i--): ?>
                                <input type="radio" name="rating" value="<?php echo $i; ?>" id="rating<?php echo $i; ?>" required>
                                <label for="rating<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">Click on a star to rate</small>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Feedback Category *</label>
                    <select class="form-select" name="category" required>
                        <option value="general">General Feedback</option>
                        <option value="usability">Usability & Navigation</option>
                        <option value="features">Features & Functionality</option>
                        <option value="content">Content & Information</option>
                        <option value="design">Design & Interface</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Your Feedback *</label>
                    <textarea class="form-control" name="feedback" rows="5" 
                              placeholder="Tell us what you think about our tourism guide system..." required></textarea>
                    <small class="text-muted">Share your experience, suggestions, or report any issues.</small>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
            </form>
        </div>
    </div>

    <!-- Recent Feedback -->
    <div class="container mt-5 mb-5">
        <h3 class="mb-4"><i class="fas fa-comments"></i> Recent Feedback from Visitors</h3>
        
        <div class="row">
            <?php if ($public_feedback->num_rows > 0): ?>
                <?php while ($fb = $public_feedback->fetch_assoc()): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card feedback-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <h5 class="card-title mb-0"><?php echo $fb['user_name']; ?></h5>
                                    <span class="badge bg-secondary"><?php echo ucfirst($fb['category']); ?></span>
                                </div>
                                <div class="rating mb-2">
                                    <?php for($i=0; $i<5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i < $fb['rating'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="card-text"><?php echo nl2br($fb['feedback']); ?></p>
                                <small class="text-muted">
                                    <i class="far fa-clock"></i> <?php echo date('M d, Y', strtotime($fb['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> No feedback yet. Be the first to share your thoughts!
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4">
        <p>&copy; 2025 Tourism Guide System. All rights reserved.</p>
        <p><small>Powered by OpenStreetMap & Leaflet (100% Free)</small></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>