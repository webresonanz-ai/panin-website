# Luxury Guest Dashboard Backend

Minimal PHP 8 + MySQL API for the Vue frontend.

## Features

- Token-based authentication middleware for protected routes
- Guest CRUD endpoints matching the current frontend fields
- MySQL schema with a seeded admin user and guest records
- Lightweight custom router/container with no Composer dependency

## Setup

1. Copy `backend/.env.example` to `backend/.env`
2. Update the database credentials in `backend/.env`
3. Import `backend/database/schema.sql` into MySQL
4. Start the API:

```bash
php -S localhost:8000 -t backend/public
```

5. Start the frontend:

```bash
cd frontend
npm run dev
```

## Default Login

- Email: `admin@luxuryhotel.test`
- Password: `password123`

## API Routes

- `POST /api/auth/login`
- `GET /api/auth/me`
- `POST /api/auth/logout`
- `GET /api/guests`
- `GET /api/guests/{id}`
- `POST /api/guests`
- `PUT /api/guests/{id}`
- `DELETE /api/guests/{id}`
