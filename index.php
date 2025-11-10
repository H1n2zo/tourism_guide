<?php
require_once 'config/database.php';

$conn = getDBConnection();

// Fetch statistics
$total_destinations = $conn->query("SELECT COUNT(*) as count FROM destinations WHERE is_active = 1")->fetch_assoc()['count'];
$total_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE is_approved = 1")->fetch_assoc()['count'];
$total_categories = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];

// Fetch categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Fetch destinations with average ratings from reviews
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';

$query = "SELECT d.*, c.name as category_name, c.icon,
          COUNT(r.id) as review_count,
          ROUND(AVG(r.rating), 1) as avg_rating
          FROM destinations d 
          LEFT JOIN categories c ON d.category_id = c.id 
          LEFT JOIN reviews r ON d.id = r.destination_id AND r.is_approved = 1
          WHERE d.is_active = 1";

if ($category_filter > 0) {
    $query .= " AND d.category_id = $category_filter";
}
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $query .= " AND (d.name LIKE '%$search_safe%' OR d.description LIKE '%$search_safe%')";
}
$query .= " GROUP BY d.id ORDER BY d.name";

$destinations = $conn->query($query);

// Get all destinations for autocomplete
$all_destinations = $conn->query("SELECT id, name FROM destinations WHERE is_active = 1 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourism Guide System - Explore Ormoc City</title>
    <meta name="description" content="Discover amazing places in Ormoc City with our interactive tourism guide. Find routes, view destinations, and plan your journey.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            padding-top: 76px;
        }
        
        .navbar {
            background: linear-gradient(135deg, #132365ff 0%, #4b59a3ff 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .hero-section {
            background: linear-gradient(135deg, #132365ff 0%, #4b59a3ff 100%),
                        url('https://source.unsplash.com/1600x900/?cebu,tourism') center/cover;
            color: white;
            padding: 80px 0;
            margin-bottom: 30px;
        }
        
        .stats-row {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            text-align: center;
            background: rgba(255,255,255,0.1);
            padding: 20px 40px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            transition: transform 0.3s;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
        }
        
        .stat-item h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }
        
        .stat-item p {
            margin: 0;
            opacity: 0.9;
        }
        
        #map {
            height: 600px;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .destination-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .destination-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .destination-card img {
            height: 200px;
            object-fit: cover;
        }
        
        .category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.9);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .search-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
        
        .route-panel {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .route-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .route-card:hover {
            border-color: #132365ff;
            box-shadow: 0 5px 15px rgba(19, 35, 101, 0.2);
            transform: translateY(-2px);
        }

        .route-card.selected {
            border-color: #132365ff;
            background: linear-gradient(135deg, rgba(19, 35, 101, 0.05) 0%, rgba(75, 89, 163, 0.05) 100%);
        }

        .route-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .route-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #132365ff;
            margin: 0;
        }

        .transport-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .transport-jeepney { background: #ffc107; color: #000; }
        .transport-taxi { background: #ffc107; color: #000; }
        .transport-bus { background: #28a745; color: #fff; }
        .transport-van { background: #17a2b8; color: #fff; }
        .transport-tricycle { background: #dc3545; color: #fff; }
        .transport-walking { background: #6c757d; color: #fff; }

        .route-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .route-detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .route-detail-item i {
            font-size: 1.3rem;
            color: #132365ff;
            width: 25px;
        }

        .route-detail-item .detail-label {
            font-size: 0.75rem;
            color: #666;
            display: block;
        }

        .route-detail-item .detail-value {
            font-weight: 600;
            color: #333;
            display: block;
        }

        .route-description {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 0.9rem;
        }

        .route-locations {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #666;
        }

        .route-arrow {
            color: #132365ff;
            font-weight: bold;
        }
        
        .rating {
            color: #ffc107;
            font-size: 0.95rem;
        }

        .rating-text {
            font-size: 0.85rem;
            color: #666;
        }

        .not-rated {
            color: #999;
            font-size: 0.85rem;
            font-style: italic;
        }

        .leaflet-popup-content {
            min-width: 200px;
        }

        .leaflet-popup-content img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 8px;
        }
        
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
        
        #backToTop:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .no-routes-message {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-routes-message i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Back to Top Button -->
    <button id="backToTop" title="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
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
                        <a class="nav-link active" href="#home"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#destinations"><i class="fas fa-map-pin"></i> Destinations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#routes"><i class="fas fa-route"></i> Find Route</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="feedback.php"><i class="fas fa-comment"></i> Feedback</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
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
    <section class="hero-section" id="home" style="margin-top: -50px">
        <div class="container text-center">
            <h1 class="display-3 fw-bold mb-3">Discover Amazing Places</h1>
            <p class="lead mb-4">Explore tourist destinations, find routes, and plan your journey</p>
            <a href="#destinations" class="btn btn-light btn-lg"><i class="fas fa-compass"></i> Start Exploring</a>
            
            <!-- Statistics Row -->
            <div class="stats-row">
                <div class="stat-item">
                    <h3><?php echo $total_destinations; ?></h3>
                    <p>Destinations</p>
                </div>
                <div class="stat-item">
                    <h3><?php echo $total_reviews; ?></h3>
                    <p>Reviews</p>
                </div>
                <div class="stat-item">
                    <h3><?php echo $total_categories; ?></h3>
                    <p>Categories</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Search and Filter Section -->
    <div class="container">
        <div class="search-box">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search destinations..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               list="destinations-list"
                               autocomplete="off">
                        <datalist id="destinations-list">
                            <?php while ($dest = $all_destinations->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($dest['name']); ?>">
                            <?php endwhile; ?>
                        </datalist>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="category">
                        <option value="0">All Categories</option>
                        <?php 
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Destinations Grid -->
    <div class="container mt-5" id="destinations">
        <h2 class="mb-4"><i class="fas fa-map-pin"></i> Featured Destinations</h2>
        <div class="row g-4">
            <?php 
            $destinations->data_seek(0);
            if ($destinations->num_rows > 0):
                while ($dest = $destinations->fetch_assoc()): 
                    $image = !empty($dest['image_path']) ? UPLOAD_URL . $dest['image_path'] : 'https://via.placeholder.com/400x300?text=' . urlencode($dest['name']);
            ?>
                <div class="col-md-4">
                    <div class="card destination-card">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($image); ?>" 
                                 class="card-img-top skeleton" 
                                 alt="<?php echo htmlspecialchars($dest['name']); ?>"
                                 loading="lazy"
                                 onload="this.classList.remove('skeleton')">
                            <span class="category-badge">
                                <i class="fas <?php echo htmlspecialchars($dest['icon']); ?>"></i> <?php echo htmlspecialchars($dest['category_name']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($dest['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($dest['description'], 0, 100)); ?>...</p>
                            <div class="mb-2">
                                <?php if ($dest['review_count'] > 0 && $dest['avg_rating'] > 0): ?>
                                    <div class="rating">
                                        <?php 
                                        $avg = round($dest['avg_rating']);
                                        for($i=0; $i<5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i < $avg ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="rating-text">
                                        <?php echo number_format($dest['avg_rating'], 1); ?> / 5 
                                        (<?php echo $dest['review_count']; ?> review<?php echo $dest['review_count'] != 1 ? 's' : ''; ?>)
                                    </small>
                                <?php else: ?>
                                    <div class="not-rated">
                                        <i class="far fa-star"></i> Not rated yet
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="destination.php?id=<?php echo $dest['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-info-circle"></i> View Details
                            </a>
                            <button class="btn btn-outline-primary btn-sm" onclick="showOnMap(<?php echo $dest['latitude']; ?>, <?php echo $dest['longitude']; ?>)">
                                <i class="fas fa-map-marker-alt"></i> Show on Map
                            </button>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else: 
            ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No destinations found</h3>
                        <p class="text-muted">Try adjusting your search or filter criteria</p>
                        <a href="index.php" class="btn btn-primary">Clear Filters</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Map Section with Route Finder -->
    <div class="container mt-5" id="routes">
        <h2 class="mb-4"><i class="fas fa-map"></i> Interactive Map & Route Finder</h2>
        
        <div class="route-panel">
            <h5 class="mb-4"><i class="fas fa-route"></i> Available Routes</h5>
            
            <?php 
            // Fetch all active routes
            $saved_routes = $conn->query("SELECT r.*, 
                o.name as origin_name, o.latitude as origin_lat, o.longitude as origin_lng,
                d.name as destination_name, d.latitude as dest_lat, d.longitude as dest_lng
                FROM routes r
                LEFT JOIN destinations o ON r.origin_id = o.id
                LEFT JOIN destinations d ON r.destination_id = d.id
                WHERE r.is_active = 1
                ORDER BY r.route_name");
            
            if ($saved_routes->num_rows > 0):
                while ($route = $saved_routes->fetch_assoc()): 
                    $route_label = $route['route_name'] ?: ($route['origin_name'] . ' to ' . $route['destination_name']);
                    $totalFare = $route['base_fare'] + ($route['distance_km'] * $route['fare_per_km']);
            ?>
                <div class="route-card" onclick='selectRoute(<?php echo json_encode($route); ?>)' data-route-id="<?php echo $route['id']; ?>">
                    <div class="route-header">
                        <h6 class="route-title"><?php echo htmlspecialchars($route_label); ?></h6>
                        <span class="transport-badge transport-<?php echo $route['transport_mode']; ?>">
                            <i class="fas fa-<?php 
                                echo $route['transport_mode'] == 'jeepney' ? 'bus' :
                                     ($route['transport_mode'] == 'taxi' ? 'taxi' :
                                     ($route['transport_mode'] == 'bus' ? 'bus-alt' :
                                     ($route['transport_mode'] == 'van' ? 'shuttle-van' :
                                     ($route['transport_mode'] == 'tricycle' ? 'motorcycle' : 'walking'))));
                            ?>"></i>
                            <?php echo ucfirst($route['transport_mode']); ?>
                        </span>
                    </div>
                    
                    <div class="route-locations">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($route['origin_name']); ?></span>
                        <span class="route-arrow"><i class="fas fa-arrow-right"></i></span>
                        <span><?php echo htmlspecialchars($route['destination_name']); ?></span>
                    </div>
                    
                    <div class="route-details">
                        <?php if ($route['distance_km']): ?>
                        <div class="route-detail-item">
                            <i class="fas fa-road"></i>
                            <div>
                                <span class="detail-label">Distance</span>
                                <span class="detail-value"><?php echo number_format($route['distance_km'], 1); ?> km</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($route['estimated_time_minutes']): ?>
                        <div class="route-detail-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <span class="detail-label">Duration</span>
                                <span class="detail-value"><?php echo $route['estimated_time_minutes']; ?> min</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($route['base_fare'] || $route['fare_per_km']): ?>
                        <div class="route-detail-item">
                            <i class="fas fa-peso-sign"></i>
                            <div>
                                <span class="detail-label">Fare</span>
                                <span class="detail-value">₱<?php echo number_format($totalFare, 2); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($route['description']): ?>
                    <div class="route-description">
                        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($route['description']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php 
                endwhile;
            else: 
            ?>
                <div class="no-routes-message">
                    <i class="fas fa-route"></i>
                    <h5>No Routes Available</h5>
                    <p>Routes will be displayed here once administrators add them to the system.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="map"></div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <p>&copy; 2025 Tourism Guide System. All rights reserved.</p>
        <p><small>Powered by OpenStreetMap & Leaflet (100% Free & Open Source)</small></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    
    <script>
        let map, routingControl, selectedRouteData = null;
        const destinations = <?php 
            $destinations->data_seek(0);
            $dest_array = [];
            while($d = $destinations->fetch_assoc()) {
                $dest_array[] = $d;
            }
            echo json_encode($dest_array);
        ?>;

        // Initialize Leaflet Map
        function initMap() {
            map = L.map('map').setView([11.0059, 124.6075], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            destinations.forEach(dest => {
                if (dest.latitude && dest.longitude) {
                    const marker = L.marker([parseFloat(dest.latitude), parseFloat(dest.longitude)])
                        .addTo(map);

                    const ratingHtml = dest.review_count > 0 
                        ? `<div style="color: #ffc107; font-size: 0.9rem;">
                             ${'★'.repeat(Math.round(dest.avg_rating))}${'☆'.repeat(5-Math.round(dest.avg_rating))}
                             <span style="color: #666; font-size: 0.85rem;">${dest.avg_rating} (${dest.review_count})</span>
                           </div>`
                        : '<div style="color: #999; font-size: 0.85rem; font-style: italic;">Not rated yet</div>';

                    const popupContent = `
                        <div>
                            ${dest.image_path ? `<img src="<?php echo UPLOAD_URL; ?>${dest.image_path}" style="width:100%;height:120px;object-fit:cover;border-radius:5px;margin-bottom:8px;">` : ''}
                            <h6>${dest.name}</h6>
                            <p><i class="fas ${dest.icon}"></i> ${dest.category_name}</p>
                            ${ratingHtml}
                            <a href="destination.php?id=${dest.id}" 
                                class="btn btn-sm btn-primary" 
                                style="margin-top:8px; background-color:#0d6efd; border:none; color:white;"
                                onmouseover="this.style.backgroundColor='black'; this.style.color='white';"
                                onmouseout="this.style.backgroundColor='#0d6efd'; this.style.color='white';">
                                View Details
                            </a>
                        </div>
                    `;

                    marker.bindPopup(popupContent);
                }
            });
        }

        function showOnMap(lat, lng) {
            map.setView([parseFloat(lat), parseFloat(lng)], 15);
            document.getElementById('routes').scrollIntoView({behavior: 'smooth'});
        }

        function selectRoute(routeData) {
            // Remove previous selection
            document.querySelectorAll('.route-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked card
            event.currentTarget.classList.add('selected');
            
            selectedRouteData = routeData;
            showRouteOnMap();
        }

        function showRouteOnMap() {
            if (!selectedRouteData) return;

            // Remove existing route
            if (routingControl) {
                map.removeControl(routingControl);
            }

            // Create new route
            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(parseFloat(selectedRouteData.origin_lat), parseFloat(selectedRouteData.origin_lng)),
                    L.latLng(parseFloat(selectedRouteData.dest_lat), parseFloat(selectedRouteData.dest_lng))
                ],
                routeWhileDragging: false,
                show: false,
                createMarker: function(i, wp, nWps) {
                    const icon = L.divIcon({
                        className: 'custom-marker',
                        html: `<div style="background: ${i === 0 ? '#28a745' : '#dc3545'}; color: white; padding: 8px 12px; border-radius: 20px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                                ${i === 0 ? '<i class="fas fa-map-marker-alt"></i> Start' : '<i class="fas fa-flag-checkered"></i> End'}
                               </div>`,
                        iconSize: [80, 40],
                        iconAnchor: [40, 40]
                    });
                    return L.marker(wp.latLng, { icon: icon });
                }
            }).on('routesfound', function(e) {
                const routes = e.routes;
                map.fitBounds(routes[0].bounds);
            }).addTo(map);

            // Scroll to map
            document.getElementById('map').scrollIntoView({ behavior: 'smooth', block: 'center' });
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

        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>