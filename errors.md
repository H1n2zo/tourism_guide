# UI change index.php
Change Route Finder from manually choosing destination from/to 
Into relying to routes table showing only routes saved data 
  `transport_mode` enum('jeepney','taxi','bus','van','tricycle','walking') NOT NULL,
  `distance_km` decimal(6,2) DEFAULT NULL,
  `estimated_time_minutes` int(11) DEFAULT NULL,
  `base_fare` decimal(8,2) DEFAULT NULL,
  `fare_per_km` decimal(8,2) DEFAULT NULL,
  `description` text DEFAULT NULL,

make card body for this with fa-icons and also with leaflet when we choose this route 
it should show the waypoint or the roadlines