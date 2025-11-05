# 🗺️ Tourism Guide System

A comprehensive web-based tourism guide application with interactive maps, destination management, route finding, and fare estimation capabilities.

## 📋 Features

### User Side
- ✨ **Interactive Maps** - FREE OpenStreetMap with Leaflet (no API key needed!)
- 🔍 **Search & Filter** - Find destinations by name or category
- 📍 **Route Finder** - Calculate routes between destinations with distance and fare estimates
- 📱 **Responsive Design** - Works seamlessly on desktop and mobile devices
- 🖼️ **Rich Media** - View destination photos and detailed information
- ⭐ **Ratings** - See ratings and reviews for destinations
- 🆓 **Completely Free** - No paid services, no limitations!

### Admin Side
- 📊 **Dashboard** - Overview of system statistics
- 🏛️ **Destination Management** - Full CRUD operations with image upload
- 🏷️ **Category Management** - Organize destinations by categories
- 🛣️ **Route Management** - Define routes with fares and transport modes
- 👥 **User Management** - Manage user accounts and roles
- 📸 **Image Upload** - Upload and manage destination images (max 5MB)
- 🗺️ **Map Picker** - Select coordinates visually on FREE map
- 📍 **GPS Location** - Capture current location automatically
- 💯 **No Costs** - Everything runs locally, no API bills!

## 🚀 Quick Start

### Prerequisites
- XAMPP (Apache + MySQL + PHP 7.4+)
- Modern web browser
- **NO API Keys Required!** 🎉

### Installation

1. **Install XAMPP**
   ```
   Download from: https://www.apachefriends.org/
   Start Apache and MySQL services
   ```

2. **Setup Project**
   ```bash
   # Navigate to htdocs
   cd C:\xampp\htdocs

   # Create project folder
   mkdir tourism_guide
   cd tourism_guide

   # Copy all project files here
   ```

3. **Create Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create database: `tourism_guide`
   - Import the SQL file provided (`tourism_db.sql`)

4. **Configure Application**
   - Edit `config/database.php`
   - Update `BASE_URL` if needed (default: `http://localhost/tourism_guide/`)
   - **That's it! No API keys needed!**

5. **Access Application**
   - User Interface: `http://localhost/tourism_guide/`
   - Admin Login: `http://localhost/tourism_guide/login.php`
   
   **Default Admin Credentials:**
   - Username: `admin`
   - Password: `admin123`

## 🗺️ Why OpenStreetMap + Leaflet?

### Free Forever ✅
- **No credit card required**
- **No API keys needed**
- **No usage limits**
- **No trial periods**
- **No expiration dates**
- **No hidden costs**

### Features We Get For FREE:
✅ Interactive maps with zoom/pan  
✅ Satellite and street view tiles  
✅ Marker placement and popups  
✅ Route calculation and directions  
✅ Distance measurement  
✅ GPS location detection  
✅ Custom map styling  
✅ Offline capability (optional)  

### Comparison with Google Maps:
| Feature | OpenStreetMap | Google Maps |
|---------|--------------|-------------|
| Cost | **FREE Forever** | $200 free/month then PAID |
| API Key | **Not Required** | Required |
| Usage Limits | **None** | Limited |
| Open Source | **Yes** | No |
| Community | **Huge** | Closed |
| Credit Card | **Not Needed** | Required |

## 📁 Project Structure

```
tourism_guide/
│
├── config/
│   └── database.php          # Database configuration (NO API KEYS!)
│
├── admin/                    # Admin panel files
│   ├── dashboard.php         # Admin dashboard
│   ├── destinations.php      # Manage destinations
│   ├── add_destination.php   # Add/Edit destination
│   ├── categories.php        # Manage categories
│   ├── routes.php            # Manage routes
│   └── users.php             # Manage users
│
├── uploads/                  # Image storage
│   ├── destinations/         # Destination images
│   └── categories/           # Category images
│
├── index.php                 # Main user interface with FREE maps
├── login.php                 # Login/Register page
├── logout.php                # Logout script
├── destination.php           # Destination details with FREE map
└── README.md                 # This file
```

## 🎯 Key Components

### FREE Mapping Technology
- **Leaflet** - Open-source JavaScript library
- **OpenStreetMap** - Free, editable world map
- **Leaflet Routing Machine** - Free route calculation
- **No server costs** - Everything runs in browser!

### Database Tables
- **users** - User accounts (admin/user roles)
- **categories** - Destination categories
- **destinations** - Tourist destinations with details
- **routes** - Routes between destinations
- **destination_images** - Multiple images per destination
- **reviews** - User reviews (future feature)

## 📸 Image Upload Guidelines

- **Supported Formats:** JPG, JPEG, PNG, GIF, WEBP
- **Maximum Size:** 5MB per image
- **Recommended Dimensions:** 1200x800 pixels (4:3 ratio)
- **Storage:** Images saved in `uploads/destinations/`
- **Naming:** Auto-generated unique filenames
- **Cost:** FREE (stored locally)

## 🛠️ Configuration

### Database Settings
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tourism_guide');
define('BASE_URL', 'http://localhost/tourism_guide/');
// NO API KEYS NEEDED! 🎉
```

### Upload Limits
Edit `php.ini` (XAMPP: `C:\xampp\php\php.ini`):
```ini
upload_max_filesize = 5M
post_max_size = 8M
max_execution_time = 300
```

## 🎯 Usage Examples

### Adding a Destination
1. Login as admin
2. Go to Destinations → Add New Destination
3. Fill in destination details
4. Set location using FREE map picker:
   - Click "Get Current Location" OR
   - Click "Pick on Map" and click location OR
   - Enter coordinates manually
5. Upload featured image
6. Save destination

### Using the FREE Map
1. Map loads automatically (no setup!)
2. Zoom in/out with mouse wheel
3. Pan by clicking and dragging
4. Click any marker to see destination info
5. Use route finder to calculate distances
6. All features work offline once loaded!

## 🔒 Security Features

- Password hashing (bcrypt)
- SQL injection prevention (prepared statements)
- XSS protection (input sanitization)
- CSRF protection (session tokens)
- Role-based access control
- File upload validation
- Secure image storage
- **No external API vulnerabilities!**

## 🐛 Troubleshooting

### Maps Not Loading
- Check internet connection (only for first load)
- Clear browser cache
- Check browser console for errors
- Ensure JavaScript is enabled

### Database Connection Failed
- Check MySQL is running in XAMPP
- Verify credentials in `config/database.php`
- Ensure database `tourism_guide` exists

### Images Not Uploading
- Check `uploads` folder exists and has write permissions
- Verify file size is under 5MB
- Check file format is supported
- Review PHP upload settings in `php.ini`

## 📊 System Requirements

- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **Apache:** 2.4 or higher
- **Browser:** Chrome 90+, Firefox 88+, Edge 90+
- **Internet:** Required for map tiles (first load only)
- **Disk Space:** 50MB + image storage
- **API Keys:** **NONE NEEDED!** 🎉

## 🎨 Customization

### Change Map Tile Provider (Optional)
OpenStreetMap offers different tile styles:
```javascript
// Default (Streets)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')

// Humanitarian (More detail)
L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png')

// Toner (Black & White)
L.tileLayer('https://stamen-tiles.a.ssl.fastly.net/toner/{z}/{x}/{y}.png')
```

### Change Color Scheme
Edit CSS variables in page `<style>` sections:
```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
}
```

## 📖 Technologies Used

- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5.3
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Maps:** OpenStreetMap + Leaflet (100% FREE!)
- **Routing:** Leaflet Routing Machine (100% FREE!)
- **Icons:** FontAwesome 6.4
- **Server:** Apache (via XAMPP)

## 💰 Cost Breakdown

| Component | Google Maps | Our Solution |
|-----------|-------------|--------------|
| Map Display | $7/1000 loads | **FREE** |
| Route Calculation | $5/1000 requests | **FREE** |
| Geocoding | $5/1000 requests | **FREE** |
| API Key | Required | **Not Needed** |
| Credit Card | Required | **Not Needed** |
| Monthly Bill | Variable | **$0.00** |
| **Total Annual Cost** | **$1000-5000+** | **$0.00** |

## 📝 Credits

**Developed by:**
- Dadios
- Gabor
- Manangot
- Pace
- Sumalinog

**Special Thanks:**
- OpenStreetMap Contributors
- Leaflet Development Team
- Open Source Community

**Date:** October 2025  
**Version:** 2.0 (FREE Edition)

## 📄 License

This project is developed for educational purposes as part of the Tourism Guide System PRD. Uses open-source technologies under their respective licenses.

## 🤝 Support

For issues or questions:
1. Check the Installation Guide
2. Review Troubleshooting section
3. Verify all requirements are met
4. Check browser console for errors
5. **No API key issues - we're FREE!** 🎉

## 🚀 Future Enhancements

Planned features (all FREE):
- User reviews and ratings system
- Weather integration (FREE API)
- Multi-language support
- Mobile app integration
- Social media sharing
- Advanced analytics
- Offline map caching
- Custom map themes

## 🎉 Benefits of Our FREE Solution

✅ **No Credit Card Required**  
✅ **No Trial Period Limits**  
✅ **No Monthly Bills**  
✅ **No Usage Restrictions**  
✅ **No Surprise Charges**  
✅ **No API Key Management**  
✅ **No Vendor Lock-in**  
✅ **Community Supported**  
✅ **Open Source**  
✅ **Forever FREE!**

---

**Happy Tourism Guide Development! 🗺️✨**

*Powered by OpenStreetMap & Leaflet - Because maps should be FREE for everyone!*