# Tourism Guide System - Installation Guide

## System Requirements
- XAMPP (includes Apache, MySQL, PHP)
- Modern web browser (Chrome, Firefox, Edge)

## Installation Steps

### 1. Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP to default location (C:\xampp on Windows)
3. Start Apache and MySQL from XAMPP Control Panel

### 2. Create Project Directory
1. Navigate to `C:\xampp\htdocs\` (Windows) or `/opt/lampp/htdocs/` (Linux)
2. Create a new folder named `tourism_guide`
3. Place all project files in this folder

### 3. Folder Structure
```
tourism_guide/
├── config/
│   └── database.php           (NO API KEYS NEEDED!)
├── admin/
│   ├── dashboard.php
│   ├── destinations.php
│   ├── add_destination.php
│   ├── categories.php
│   ├── routes.php
│   └── users.php
├── uploads/
│   ├── destinations/
│   └── categories/
├── index.php                  (FREE Maps included!)
├── login.php
├── logout.php
├── destination.php            (FREE Map view!)
└── README.md
```

### 4. Setup Database

#### Option A: Using phpMyAdmin (Recommended)
1. Open browser and go to `http://localhost/phpmyadmin`
2. Click "New" to create a new database
3. Name it `tourism_guide` and click "Create"
4. Click on the database name in left sidebar
5. Click "SQL" tab
6. Copy and paste the contents of `tourism_db.sql` file
7. Click "Go" to execute

#### Option B: Using SQL File
1. Save the database schema as `tourism_db.sql`
2. Open XAMPP Control Panel
3. Click "Shell" button
4. Run: `mysql -u root -p tourism_guide < /path/to/tourism_db.sql`

### 5. Configure Application

#### Update config/database.php
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP has no password
define('DB_NAME', 'tourism_guide');

define('BASE_URL', 'http://localhost/tourism_guide/');

// NO API KEYS REQUIRED! 
// Using FREE OpenStreetMap + Leaflet
define('MAP_PROVIDER', 'leaflet');
```

**That's it! No API key setup needed!** 🎉

### 6. Create Required Folders
The system will automatically create upload folders, but you can manually create them:
```
mkdir uploads
mkdir uploads/destinations
mkdir uploads/categories
```

Set permissions (Linux/Mac):
```bash
chmod 755 uploads
chmod 755 uploads/destinations
chmod 755 uploads/categories
```

### 7. Access the Application

#### User Side
- URL: `http://localhost/tourism_guide/`
- Browse destinations with FREE interactive maps!
- No login required for browsing

#### Admin Panel
- URL: `http://localhost/tourism_guide/login.php`
- Default Admin Credentials:
  - Username: `admin`
  - Password: `admin123`

### 8. First Time Setup

1. **Login as Admin**
   - Go to login page
   - Use default credentials
   - Change password after first login (recommended)

2. **Categories Available** (Pre-loaded)
   - Tourist Spot
   - Restaurant
   - Hotel
   - Transport Terminal
   - Landmark
   - Nature
   - Shopping
   - Entertainment

3. **Add Your First Destination**
   - Click "Add New Destination"
   - Fill in all required fields
   - Upload image (max 5MB)
   - Set coordinates using FREE map picker:
     - **Option 1:** Click "Get Current Location" (uses browser GPS)
     - **Option 2:** Click "Pick on Map" and click anywhere on the FREE map
     - **Option 3:** Enter coordinates manually
   - All done - no API keys needed!
   - Save destination

4. **Test the FREE Maps**
   - Go to homepage
   - See all destinations on FREE OpenStreetMap
   - Use route finder (completely FREE!)
   - Calculate distances and fares (FREE!)
   - Everything works offline after first load!

## ✨ Features Overview

### User Side (All FREE!)
- Interactive OpenStreetMap with all destinations
- Route finder with fare estimation (no API costs!)
- Search and filter destinations
- View destination details with maps
- Responsive design (mobile-friendly)
- No usage limits or restrictions!

### Admin Side (All FREE!)
- Dashboard with statistics
- Full CRUD for Destinations
- Category management
- Route management
- Image upload with preview
- FREE map coordinate picker
- User management
- No API costs or limits!

## 🗺️ Why No API Key Needed?

### We Use OpenStreetMap + Leaflet:
✅ **100% Free & Open Source**  
✅ **No Registration Required**  
✅ **No Credit Card Needed**  
✅ **No Usage Limits**  
✅ **No Expiration Dates**  
✅ **No Hidden Costs**  
✅ **Community Maintained**  
✅ **Better for Education**  
✅ **Privacy Friendly**  
✅ **Works Offline (after cache)**  

### What You Get For FREE:
- Interactive maps with zoom/pan
- Street view tiles
- Marker placement and popups
- Route calculation
- Distance measurement
- GPS location detection
- Custom styling options
- No monthly bills!

## 📸 Image Upload Guidelines
- **Supported formats:** JPG, JPEG, PNG, GIF, WEBP
- **Maximum size:** 5MB per image
- **Recommended size:** 1200x800 pixels
- **Image path:** Stored in `uploads/destinations/`
- **Naming:** System generates unique filenames
- **Cost:** FREE (local storage)

## 🐛 Troubleshooting

### Maps Not Loading
- **Check internet connection** (needed for first load)
- Clear browser cache
- Check browser console (F12) for errors
- Ensure JavaScript is enabled
- **No API key issues - we're FREE!**

### Database Connection Error
- Check if MySQL is running in XAMPP
- Verify database credentials in `config/database.php`
- Ensure `tourism_guide` database exists

### Image Upload Not Working
- Check folder permissions (755 or 777)
- Verify `uploads` folder exists
- Check PHP upload settings in `php.ini`:
  ```
  upload_max_filesize = 5M
  post_max_size = 8M
  ```

### Route Finder Not Working
- Check internet connection (for routing API)
- Ensure both origin and destination are selected
- Check browser console for errors
- **No API key needed - it's FREE!**

### Page Not Found (404)
- Check if Apache is running
- Verify file paths match folder structure
- Check `.htaccess` if using custom URLs

### Cannot Login
- Verify database has default admin user
- Check password: `admin123`
- Look for error messages in browser

## 🔒 Security Recommendations

1. **Change Default Admin Password**
   - Login as admin
   - Go to profile settings
   - Change password immediately

2. **Protect Config File**
   - Never commit `database.php` with real credentials to public repositories
   - Use environment variables for production

3. **Enable HTTPS** (Production)
   - Get SSL certificate
   - Force HTTPS in production

4. **Regular Backups**
   - Backup database regularly
   - Backup uploaded images
   - Use phpMyAdmin export feature

## 📊 Additional Configuration

### Change Upload Limits
Edit `php.ini` (in XAMPP: `C:\xampp\php\php.ini`):
```ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300
```
Restart Apache after changes.

### Add More Categories
1. Login as admin
2. Go to Categories page
3. Add category with name and FontAwesome icon
4. Available icons: https://fontawesome.com/icons

### Customize Map Style (Optional)
Change map tiles in your PHP files:
```javascript
// Default (Streets)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')

// Humanitarian (More colors)
L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png')

// Dark Mode
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png')
```

## 💰 Cost Comparison

| Feature | Google Maps | Our Solution |
|---------|-------------|--------------|
| Setup Cost | $0 (requires CC) | **$0 (no CC)** |
| Monthly Free | $200 credit | **Unlimited** |
| After Free Tier | $7+ per 1000 loads | **$0.00** |
| API Key | Required | **Not Needed** |
| Credit Card | Required | **Not Needed** |
| Usage Limits | Yes | **No Limits** |
| **Annual Cost** | **$500-5000+** | **$0.00** |

## ✅ Installation Checklist

- [ ] XAMPP installed and running (Apache + MySQL)
- [ ] `tourism_guide` folder created in htdocs
- [ ] All PHP files copied to correct locations
- [ ] `uploads` folder and subfolders created
- [ ] Database `tourism_guide` created
- [ ] SQL file imported successfully
- [ ] `config/database.php` configured
- [ ] Accessed `http://localhost/tourism_guide/` successfully
- [ ] Can login with admin/admin123
- [ ] **NO API KEYS NEEDED - Already FREE!** ✅

## 🎉 Verification Steps

Test these to ensure everything works:

1. **Homepage Loads**
   - Go to `http://localhost/tourism_guide/`
   - See FREE interactive map
   - All destinations visible

2. **Admin Access**
   - Login with admin/admin123
   - Dashboard displays statistics

3. **Add Destination**
   - Click "Add New Destination"
   - Fill form and upload image
   - Use FREE map picker to set location
   - Save successfully

4. **View Destination**
   - Click on any destination
   - See details with FREE map
   - Image displays correctly

5. **Route Finder**
   - Select origin and destination
   - Click "Find"
   - See route on FREE map with distance and fare

If all tests pass: **Congratulations! Your FREE Tourism Guide System is ready!** 🎉

## 🆘 Support Resources

- **PHP Documentation:** https://www.php.net/docs.php
- **MySQL Documentation:** https://dev.mysql.com/doc/
- **Bootstrap Documentation:** https://getbootstrap.com/docs/
- **Leaflet Documentation:** https://leafletjs.com/reference.html
- **OpenStreetMap:** https://www.openstreetmap.org/

## 🚀 Quick Start Summary

1. Install XAMPP ✅
2. Create `tourism_guide` folder in htdocs ✅
3. Copy all files ✅
4. Create database and import SQL ✅
5. Configure `config/database.php` ✅
6. Access `http://localhost/tourism_guide/` ✅
7. **NO API KEY SETUP NEEDED!** ✅
8. **Start using FREE maps immediately!** ✅

## 🎁 What You Get (All FREE!)

✅ Interactive Maps (OpenStreetMap)  
✅ Route Finding (Leaflet Routing)  
✅ Distance Calculation  
✅ Fare Estimation  
✅ GPS Location Detection  
✅ Unlimited Destinations  
✅ Unlimited Users  
✅ Unlimited Map Loads  
✅ No Monthly Costs  
✅ No Hidden Fees  
✅ No Expiration  
✅ Forever FREE!  

---

## 📞 Need Help?

If you encounter issues:
1. Check this guide thoroughly
2. Verify all installation steps
3. Check XAMPP Control Panel (Apache and MySQL must be green)
4. Check browser console (F12) for errors
5. **Remember: No API keys needed - one less thing to worry about!** 😊

## 🎊 Enjoy Your FREE Tourism Guide System!

**No credit cards. No trials. No limits. Just pure, free, open-source tourism mapping!**

---

**Version:** 2.0 (FREE Edition)  
**Last Updated:** November 2025  
**Cost:** $0.00 Forever! 🎉