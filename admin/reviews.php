<?php
require_once '../config/database.php';
requireAdmin();

$conn = getDBConnection();
$success = '';

// Handle delete review
if (isset($_GET['delete_review'])) {
    $id = (int)$_GET['delete_review'];
    $conn->query("DELETE FROM reviews WHERE id = $id");
    $success = "Review deleted successfully!";
}

// Handle delete feedback
if (isset($_GET['delete_feedback'])) {
    $id = (int)$_GET['delete_feedback'];
    $conn->query("DELETE FROM website_feedback WHERE id = $id");
    $success = "Feedback deleted successfully!";
}

// Handle approve/disapprove review
if (isset($_GET['toggle_review'])) {
    $id = (int)$_GET['toggle_review'];
    $conn->query("UPDATE reviews SET is_approved = NOT is_approved WHERE id = $id");
    $success = "Review status updated!";
}

// Handle mark feedback as read
if (isset($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    $conn->query("UPDATE website_feedback SET is_read = 1 WHERE id = $id");
    $success = "Feedback marked as read!";
}

// Fetch all reviews
$reviews = $conn->query("SELECT r.*, d.name as destination_name 
                         FROM reviews r 
                         LEFT JOIN destinations d ON r.destination_id = d.id 
                         ORDER BY r.created_at DESC");

// Fetch all website feedback
$feedbacks = $conn->query("SELECT * FROM website_feedback ORDER BY is_read ASC, created_at DESC");

// Statistics
$total_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];
$pending_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE is_approved = 0")->fetch_assoc()['count'];
$total_feedback = $conn->query("SELECT COUNT(*) as count FROM website_feedback")->fetch_assoc()['count'];
$unread_feedback = $conn->query("SELECT COUNT(*) as count FROM website_feedback WHERE is_read = 0")->fetch_assoc()['count'];
$avg_website_rating = $conn->query("SELECT AVG(rating) as avg FROM website_feedback")->fetch_assoc()['avg'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews & Feedback - Tourism Admin</title>
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
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .rating {
            color: #ffc107;
        }
        .unread {
            background-color: #fff3cd;
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
        <h2 class="mb-4"><i class="fas fa-star"></i> Reviews & Feedback Management</h2>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card" style="border-color: #667eea;">
                    <div class="card-body">
                        <h6 class="text-muted">Total Reviews</h6>
                        <h2><?php echo $total_reviews; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card" style="border-color: #ffc107;">
                    <div class="card-body">
                        <h6 class="text-muted">Pending Reviews</h6>
                        <h2><?php echo $pending_reviews; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card" style="border-color: #28a745;">
                    <div class="card-body">
                        <h6 class="text-muted">Website Feedback</h6>
                        <h2><?php echo $total_feedback; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card" style="border-color: #17a2b8;">
                    <div class="card-body">
                        <h6 class="text-muted">Avg Website Rating</h6>
                        <h2><?php echo $avg_website_rating ? round($avg_website_rating, 1) : 'N/A'; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#reviews">
                    <i class="fas fa-comments"></i> Destination Reviews (<?php echo $total_reviews; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#feedback">
                    <i class="fas fa-comment-dots"></i> Website Feedback (<?php echo $total_feedback; ?>)
                    <?php if ($unread_feedback > 0): ?>
                        <span class="badge bg-danger"><?php echo $unread_feedback; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Destination Reviews Tab -->
            <div class="tab-pane fade show active" id="reviews">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Destination</th>
                                        <th>User</th>
                                        <th>Rating</th>
                                        <th>Comment</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($reviews->num_rows > 0): ?>
                                        <?php while ($review = $reviews->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?php echo $review['destination_name']; ?></strong></td>
                                                <td><?php echo $review['user_name']; ?></td>
                                                <td>
                                                    <div class="rating">
                                                        <?php for($i=0; $i<5; $i++): ?>
                                                            <i class="fas fa-star<?php echo $i < $review['rating'] ? '' : '-o'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                                <td><?php echo substr($review['comment'], 0, 50) . '...'; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($review['is_approved']): ?>
                                                        <span class="badge bg-success">Approved</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="../destination.php?id=<?php echo $review['destination_id']; ?>" 
                                                       class="btn btn-sm btn-info" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="?toggle_review=<?php echo $review['id']; ?>" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </a>
                                                    <a href="?delete_review=<?php echo $review['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Delete this review?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                                <p class="text-muted">No reviews yet</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Website Feedback Tab -->
            <div class="tab-pane fade" id="feedback">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Rating</th>
                                        <th>Feedback</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($feedbacks->num_rows > 0): ?>
                                        <?php while ($fb = $feedbacks->fetch_assoc()): ?>
                                            <tr class="<?php echo !$fb['is_read'] ? 'unread' : ''; ?>">
                                                <td>
                                                    <strong><?php echo $fb['user_name']; ?></strong>
                                                    <?php if ($fb['email']): ?>
                                                        <br><small><?php echo $fb['email']; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge bg-secondary"><?php echo ucfirst($fb['category']); ?></span></td>
                                                <td>
                                                    <div class="rating">
                                                        <?php for($i=0; $i<5; $i++): ?>
                                                            <i class="fas fa-star<?php echo $i < $fb['rating'] ? '' : '-o'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                                <td><?php echo substr($fb['feedback'], 0, 60) . '...'; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($fb['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($fb['is_read']): ?>
                                                        <span class="badge bg-success">Read</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">New</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!$fb['is_read']): ?>
                                                        <a href="?mark_read=<?php echo $fb['id']; ?>" 
                                                           class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-info" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#feedbackModal<?php echo $fb['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="?delete_feedback=<?php echo $fb['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Delete this feedback?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>

                                            <!-- Feedback Modal -->
                                            <div class="modal fade" id="feedbackModal<?php echo $fb['id']; ?>">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Feedback Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>From:</strong> <?php echo $fb['user_name']; ?></p>
                                                            <?php if ($fb['email']): ?>
                                                                <p><strong>Email:</strong> <?php echo $fb['email']; ?></p>
                                                            <?php endif; ?>
                                                            <p><strong>Category:</strong> <?php echo ucfirst($fb['category']); ?></p>
                                                            <p><strong>Rating:</strong> 
                                                                <span class="rating">
                                                                    <?php for($i=0; $i<5; $i++): ?>
                                                                        <i class="fas fa-star<?php echo $i < $fb['rating'] ? '' : '-o'; ?>"></i>
                                                                    <?php endfor; ?>
                                                                </span>
                                                            </p>
                                                            <p><strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($fb['created_at'])); ?></p>
                                                            <hr>
                                                            <p><strong>Feedback:</strong></p>
                                                            <p><?php echo nl2br($fb['feedback']); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                                <p class="text-muted">No feedback yet</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>