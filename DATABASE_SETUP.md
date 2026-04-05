# Skilora App - Database Setup & Login Configuration

## Database Connection Setup

Your application has been configured to connect to MySQL database. Follow these steps to complete the setup:

### Step 1: Ensure MySQL is Running

Make sure MySQL/MariaDB is running on your system. You can check this:

**Windows (Command Prompt):**
```bash
mysql -u root -p
```

If you can connect to MySQL, you're good to go.

### Step 2: Import the Database

You have the SQL file: `skilora (1).sql`

Replace the database with the skilora data:

**Option A: Using MySQL Command Line**
```bash
cd c:\Users\mouhamed aziz khaldi\Desktop\WEB
mysql -u root -p -e "CREATE DATABASE `skilora (1)`;"
mysql -u root -p "skilora (1)" < "skilora (1).sql"
```

**Option B: Using phpMyAdmin**
1. Open phpMyAdmin (usually at http://localhost/phpmyadmin)
2. Click on "New" to create a new database named `skilora (1)`
3. Click on the new `skilora (1)` database
4. Go to "Import" tab
5. Upload the `skilora (1).sql` file
6. Click "Go" to import

**Option C: Using HeidiSQL (if installed)**
1. Open HeidiSQL
2. Connect to your MySQL server
3. Right-click on the server and select "Create > Database"
4. Name it `skilora (1)`
5. Right-click on `skilora (1)` database
6. Select "Load SQL file..."
7. Choose the `skilora (1).sql` file

### Step 3: Update Database Connection (if needed)

The `.env.local` file has been created with default MySQL settings:

```
DATABASE_URL="mysql://root:@127.0.0.1:3306/skilora%20(1)"
```

If your MySQL has a password, update it:
```
DATABASE_URL="mysql://root:YOUR_PASSWORD@127.0.0.1:3306/skilora%20(1)"
```

### Step 4: Start Your Application

Once the database is imported, run:

```bash
cd c:\Users\mouhamed aziz khaldi\Desktop\WEB\WEB
php bin/console server:run
```

Or use Symfony CLI:
```bash
symfony server:start
```

### Step 5: Access Your Application

Open your browser and go to:
```
http://localhost:8000
```

You will be automatically redirected to the login page.

## Test Credentials

Use these credentials to login (from the database):

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| User | user | user123 |
| Employer | employer | emp123 |
| Nour | nour | (password was hashed, use register new user) |

## Login Routes

- **Login Page**: http://localhost:8000/login
- **Register Page**: http://localhost:8000/register
- **Dashboard**: http://localhost:8000/dashboard
- **Logout**: http://localhost:8000/logout

## Important Notes

1. **Password Hashing**: New passwords registered through the app will be hashed with bcrypt
2. **Existing Passwords**: The test credentials in the database use plain text. Symfony will hash them when you first login
3. **CSRF Protection**: All forms have CSRF protection enabled
4. **Remember Me**: Login page has "Remember me" functionality that keeps you logged in for 1 week

## Troubleshooting

### Database Connection Error
If you get a connection error:
1. Make sure MySQL is running
2. Check the DATABASE_URL in `.env.local`
3. Try to connect with MySQL CLI: `mysql -u root -p`

### Cache Clear Error
If cache:clear fails on composer install:
```bash
cd c:\Users\mouhamed aziz khaldi\Desktop\WEB\WEB
php bin/console cache:clear
php bin/console cache:warmup
```

### Page Not Found
If you get "No route found":
1. Make sure your controllers are in the correct namespace: `App\Controller`
2. Run: `php bin/console debug:router` to see all available routes

### Login Not Working
1. Verify the database is connected: `php bin/console doctrine:query:sql "SELECT COUNT(*) FROM users"`
2. Check if user exists in database
3. Review config/packages/security.yaml - it should look for users by username property
