# Website Blog API

A RESTful API for a blog website built with SlimPHP.

## Project Structure

```
/WebsiteBlog/
  /app
    /Controllers    # Controller classes
    /Models         # Model classes
    /Middleware     # Middleware classes
    /Helpers        # Helper classes
  /config           # Configuration files
  /database         # Database scripts
  /public           # Public accessible files (entry point)
  /vendor           # Composer dependencies (will be created)
  .env              # Environment variables
  composer.json     # Composer configuration
```

## Requirements

- PHP 7.4 or higher
- Composer
- MySQL/MariaDB

## Installation

1. Clone the repository
2. Navigate to the project directory
3. Install dependencies:

```bash
composer install
```

4. Create a database and import the schema:

```bash
mysql -u root -p < database/init.sql
```

5. Configure environment variables by editing the `.env` file:

```
DB_HOST=localhost
DB_NAME=websiteblog
DB_USER=your_db_user
DB_PASS=your_db_password
JWT_SECRET=your_jwt_secret_key_here
APP_ENV=development
```

6. Start the server (using PHP's built-in server for development):

```bash
cd public
php -S localhost:8000
```

## API Endpoints

### Authentication

- **POST /api/auth/register** - Register a new user
  - Request body:
    ```json
    {
      "username": "newuser",
      "email": "user@example.com",
      "password": "password123"
    }
    ```

- **POST /api/auth/login** - Login and receive JWT token
  - Request body:
    ```json
    {
      "username": "newuser", 
      "password": "password123"
    }
    ```
  - Response:
    ```json
    {
      "status": "success",
      "message": "Login successful",
      "data": {
        "token": "eyJ0eXAiOiJKV...",
        "expires": 1632355200,
        "user": {
          "id": 1,
          "username": "newuser",
          "email": "user@example.com",
          "role": "user"
        }
      }
    }
    ```

- **POST /api/auth/verify-email** - Verify user email
  - Request body:
    ```json
    {
      "token": "verification_token_here"
    }
    ```

- **GET /api/auth/me** - Get current user info (requires authentication)
  - Headers:
    ```
    Authorization: Bearer YOUR_JWT_TOKEN
    ```
  - Response:
    ```json
    {
      "status": "success",
      "data": {
        "id": 1,
        "username": "newuser",
        "email": "user@example.com",
        "role": "user",
        "status": "active",
        "created_at": "2023-09-20 12:00:00"
      }
    }
    ```

## Authentication

The API uses JWT (JSON Web Token) for authentication. To access protected endpoints:

1. Login to get the token
2. Include the token in subsequent requests:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

## Demo Users

Two demo users are included in the database initialization script:

1. Admin user:
   - Username: admin
   - Email: admin@example.com
   - Password: admin123
   - Role: admin

2. Regular user:
   - Username: user
   - Email: user@example.com
   - Password: user123
   - Role: user
