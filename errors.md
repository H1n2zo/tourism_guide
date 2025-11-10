# UI change index.php
Fix leaflet to show starting point to end point
with yellow polyline when clicking the available routes
also make filter by destinations and transport

CREATE TABLE `routes` (
  `origin_id` int(11) DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
