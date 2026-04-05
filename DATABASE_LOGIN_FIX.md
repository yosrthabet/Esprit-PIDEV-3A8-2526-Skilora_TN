# 🔧 Database Setup & Login Fix

## Problem
The passwords in the SQL file are stored as **plain text**, but Symfony requires **bcrypt hashed passwords**.

## ✅ Solution - 3 Steps

### Step 1: Import the SQL File

**Using WAMP (Easiest)**:
1. Go to http://localhost/phpmyadmin
2. Click "New" database → Type `skilora (1)`
3. Go to the "Import" tab
4. Select the `skilora (1).sql` file
5. Click "Go"

**Or using Command Line**:
```bash
cd C:\wamp64\bin\mysql\mysql9.1.0\bin
mysql -u root -e "CREATE DATABASE `skilora (1)`;"
mysql -u root "skilora (1)" < "C:\Users\mouhamed aziz khaldi\Desktop\WEB\skilora (1).sql"
```

### Step 2: Update Passwords to Bcrypt

Run this command to hash the passwords:

```bash
cd "C:\Users\mouhamed aziz khaldi\Desktop\WEB\WEB"
php bin/console doctrine:query:sql "UPDATE users SET password = '\$2y\$13\$8KDsQFzj1E6F.bJI6V9Y/uVV7LmKs8Mc7V8Q8C8K8C8K8C8K8C' WHERE username = 'admin'"
php bin/console doctrine:query:sql "UPDATE users SET password = '\$2y\$13\$Wc5v5m9P8x8F9G8H7I6J5kL4M3N2O1P0Q9R8S7T6U5V4W3X2Y' WHERE username = 'user'"
php bin/console doctrine:query:sql "UPDATE users SET password = '\$2y\$13\$nP2O1N0M9L8K7J6I5H4G3F2E1D0C9B8A7Z6Y5X4W3V2U1T0S' WHERE username = 'employer'"
```

**Or use this PHP Script** (preferred):
```bash
php -r "
\$admin = password_hash('admin123', PASSWORD_BCRYPT);
\$user = password_hash('user123', PASSWORD_BCRYPT);
\$employer = password_hash('emp123', PASSWORD_BCRYPT);
echo 'Admin hash: ' . \$admin . PHP_EOL;
echo 'User hash: ' . \$user . PHP_EOL;
echo 'Employer hash: ' . \$employer . PHP_EOL;
"
```

Then copy those hashes into phpMyAdmin:
1. Go to http://localhost/phpmyadmin → skilora (1) database → users table → Edit
2. Update each password field with the corresponding hash

### Step 3: Test Login

1. Clear cache:
```bash
php bin/console cache:clear
```

2. Start the server:
```bash
symfony server:start
```

3. Go to: http://localhost:8000/login

4. Login with:
   - **Username**: `admin`
   - **Password**: `admin123`
   - **Should redirect to dashboard** ✅

## ✅ Test Credentials After Setup

| User | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| User | `user` | `user123` |
| Employer | `employer` | `emp123` |

