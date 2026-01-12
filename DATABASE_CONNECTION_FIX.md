# Database Connection Error Fix

## Error: "No connection could be made because the target machine actively refused it"

This error means Laravel cannot connect to your MySQL/MariaDB database server.

## Quick Fix Steps:

### 1. Check if MySQL/MariaDB is Running

**For XAMPP:**
- Open XAMPP Control Panel
- Make sure MySQL service is **STARTED** (green)
- If it's stopped, click "Start"

**For WAMP:**
- Open WAMP Control Panel
- Make sure MySQL service is running (green icon)
- If not, click on WAMP icon → MySQL → Service → Start/Resume Service

**For Laragon:**
- Open Laragon
- Make sure MySQL is running (green indicator)
- If not, click "Start All"

**For Standalone MySQL:**
- Open Services (Win + R, type `services.msc`)
- Find "MySQL" or "MariaDB" service
- Right-click → Start (if stopped)

### 2. Check Your .env File

Open `_merchantrack/.env` and verify these settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=root
DB_PASSWORD=your_password
```

**Common Issues:**
- If using XAMPP, password is usually empty: `DB_PASSWORD=`
- If using WAMP, password is usually empty: `DB_PASSWORD=`
- If using Laragon, password is usually empty: `DB_PASSWORD=`
- Port 3306 is default, but some installations use 3307

### 3. Test Database Connection

Try connecting to MySQL using command line:
```bash
mysql -u root -p
```
Or if no password:
```bash
mysql -u root
```

If this fails, MySQL is not running or not in your PATH.

### 4. Check Database Exists

Make sure your database exists:
```sql
SHOW DATABASES;
```

If your database doesn't exist, create it:
```sql
CREATE DATABASE your_database_name;
```

### 5. Clear Laravel Cache

After fixing .env, run:
```bash
php artisan config:clear
php artisan cache:clear
```

### 6. Alternative: Use SQLite (For Testing)

If MySQL continues to have issues, you can temporarily use SQLite:

1. In `.env`, change:
```env
DB_CONNECTION=sqlite
# Comment out or remove MySQL settings
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=your_database_name
# DB_USERNAME=root
# DB_PASSWORD=
```

2. Create database file:
```bash
touch database/database.sqlite
```

3. Run migrations:
```bash
php artisan migrate
```

## Still Having Issues?

1. **Check Windows Firewall** - Make sure it's not blocking MySQL
2. **Check MySQL Port** - Try changing port to 3307 in .env if 3306 doesn't work
3. **Check MySQL Error Logs** - Look in your MySQL installation directory for error logs
4. **Restart Your Computer** - Sometimes services need a full restart

## Quick Test Script

Create a file `test-db.php` in your project root:

```php
<?php
try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3306",
        "root",
        ""
    );
    echo "Database connection successful!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

Run it: `php test-db.php`

If this fails, MySQL is definitely not running or accessible.

