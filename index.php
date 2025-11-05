<?php
require_once 'config/database.php';

$conn = getDBConnection();

// Fetch categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Fetch destinations
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$query = "SELECT d.*, c.name as category_name, c.icon 
          FROM destinations d 
          LEFT JOIN categories c ON d.category_id = c.id 
          WHERE d.is_active = 1";

if ($category_filter > 0) {
    $query .= " AND d.category_id = $category_filter";
}
if (!empty($search)) {
    $query .= " AND (d.name LIKE '%$search%' OR d.description LIKE '%$search%')";
}
$query .= " ORDER BY d.name";

$destinations = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourism Guide System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS - FREE OpenStreetMap -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            padding-top: 76px;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .hero-section {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%),
                        url('https://source.unsplash.com/1600x900/?cebu,tourism') center/cover;
            color: white;
            padding: 80px 0;
            margin-bottom: 30px;
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
        
        .filter-btn {
            margin: 5px;
        }
        
        .route-panel {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .rating {
            color: #ffc107;
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
    </style>
</head>
<body>
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
    <section class="hero-section" id="home">
        <div class="container text-center">
            <h1 class="display-3 fw-bold mb-3">Discover Amazing Places</h1>
            <p class="lead mb-4">Explore tourist destinations, find routes, and plan your journey</p>
            <a href="#destinations" class="btn btn-light btn-lg"><i class="fas fa-compass"></i> Start Exploring</a>
        </div>
    </section>

    <!-- Search and Filter Section -->
    <div class="container">
        <div class="search-box">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search destinations..." value="<?php echo $search; ?>">
                    </div>
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
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Map Section -->
    <div class="container mt-5" id="routes">
        <h2 class="mb-4"><i class="fas fa-map"></i> Interactive Map</h2>
        
        <div class="route-panel">
            <h5>Find Route & Estimate Fare</h5>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">From</label>
                    <select class="form-select" id="origin">
                        <option value="">Select Origin</option>
                        <?php $destinations->data_seek(0); while ($dest = $destinations->fetch_assoc()): ?>
                            <option value="<?php echo $dest['latitude'].','.$dest['longitude']; ?>" data-name="<?php echo $dest['name']; ?>">
                                <?php echo $dest['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">To</label>
                    <select class="form-select" id="destination">
                        <option value="">Select Destination</option>
                        <?php $destinations->data_seek(0); while ($dest = $destinations->fetch_assoc()): ?>
                            <option value="<?php echo $dest['latitude'].','.$dest['longitude']; ?>" data-name="<?php echo $dest['name']; ?>">
                                <?php echo $dest['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" onclick="calculateRoute()">
                        <i class="fas fa-search-location"></i> Find
                    </button>
                </div>
            </div>
            <div id="routeInfo" class="mt-3" style="display:none;">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Route Information</h6>
                    <p class="mb-1"><strong>Distance:</strong> <span id="distance"></span></p>
                    <p class="mb-1"><strong>Estimated Duration:</strong> <span id="duration"></span></p>
                    <p class="mb-0"><strong>Estimated Fare:</strong> <span id="fare"></span></p>
                </div>
            </div>
        </div>
        
        <div id="map"></div>
    </div>

    <!-- Destinations Grid -->
    <div class="container mt-5" id="destinations">
        <h2 class="mb-4"><i class="fas fa-map-pin"></i> Featured Destinations</h2>
        <div class="row g-4">
            <?php 
            $destinations->data_seek(0);
            while ($dest = $destinations->fetch_assoc()): 
                $image = !empty($dest['image_path']) ? UPLOAD_URL . $dest['image_path'] : 'https://via.placeholder.com/400x300?text=' . urlencode($dest['name']);
            ?>
                <div class="col-md-4">
                    <div class="card destination-card">
                        <div class="position-relative">
                            <img src="<?php echo $image; ?>" class="card-img-top" alt="<?php echo $dest['name']; ?>">
                            <span class="category-badge">
                                <i class="fas <?php echo $dest['icon']; ?>"></i> <?php echo $dest['category_name']; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $dest['name']; ?></h5>
                            <p class="card-text"><?php echo substr($dest['description'], 0, 100); ?>...</p>
                            <div class="mb-2">
                                <?php if ($dest['rating'] > 0): ?>
                                    <span class="rating">
                                        <?php for($i=0; $i<5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i < $dest['rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                        <?php echo $dest['rating']; ?>
                                    </span>
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
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <p>&copy; 2025 Tourism Guide System. All rights reserved.</p>
        <p><small>Powered by OpenStreetMap & Leaflet (100% Free & Open Source)</small></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet JS - FREE OpenStreetMap -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    
    <script>
        let map, routingControl;
        const destinations = <?php 
            $destinations->data_seek(0);
            $dest_array = [];
            while($d = $destinations->fetch_assoc()) {
                $dest_array[] = $d;
            }
            echo json_encode($dest_array);
        ?>;

        // Initialize Leaflet Map (FREE!)
        function initMap() {
            // Center on Cebu City
            map = L.map('map').setView([10.3157, 123.8854], 12);
            
            // Add OpenStreetMap tiles (FREE!)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            // Add markers for all destinations
            destinations.forEach(dest => {
                if (dest.latitude && dest.longitude) {
                    const marker = L.marker([parseFloat(dest.latitude), parseFloat(dest.longitude)])
                        .addTo(map);

                    const popupContent = `
                        <div>
                            ${dest.image_path ? `<img src="<?php echo UPLOAD_URL; ?>${dest.image_path}" style="width:100%;height:120px;object-fit:cover;border-radius:5px;margin-bottom:8px;">` : ''}
                            <h6>${dest.name}</h6>
                            <p><i class="fas ${dest.icon}"></i> ${dest.category_name}</p>
                            <a href="destination.php?id=${dest.id}" class="btn btn-sm btn-primary">View Details</a>
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

        function calculateRoute() {
            const origin = document.getElementById('origin').value;
            const destination = document.getElementById('destination').value;

            if (!origin || !destination) {
                alert('Please select both origin and destination');
                return;
            }

            const originCoords = origin.split(',');
            const destCoords = destination.split(',');

            // Remove previous routing if exists
            if (routingControl) {
                map.removeControl(routingControl);
            }

            // Calculate route using Leaflet Routing Machine (FREE!)
            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(parseFloat(originCoords[0]), parseFloat(originCoords[1])),
                    L.latLng(parseFloat(destCoords[0]), parseFloat(destCoords[1]))
                ],
                routeWhileDragging: false,
                show: false,
                createMarker: function() { return null; } // Don't create default markers
            }).on('routesfound', function(e) {
                const routes = e.routes;
                const summary = routes[0].summary;
                
                // Distance in km
                const distance = (summary.totalDistance / 1000).toFixed(2);
                
                // Time in minutes
                const duration = Math.round(summary.totalTime / 60);
                
                // Simple fare calculation (base PHP 40 + PHP 10 per km)
                const baseFare = 40;
                const farePerKm = 10;
                const estimatedFare = baseFare + (distance * farePerKm);
                
                document.getElementById('distance').textContent = distance + ' km';
                document.getElementById('duration').textContent = duration + ' minutes';
                document.getElementById('fare').textContent = 'PHP ' + estimatedFare.toFixed(2) + ' (Jeepney/Bus estimate)';
                document.getElementById('routeInfo').style.display = 'block';
            }).addTo(map);
        }

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>