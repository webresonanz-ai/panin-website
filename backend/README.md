# Luxury Guest Dashboard Backend

Minimal PHP 8 + MySQL API for the Vue frontend.

## Features

- Token-based authentication middleware for protected routes
- Guest CRUD endpoints matching the current frontend fields
- Excel guest import endpoint for `.xlsx` and `.csv` files
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
- `POST /api/guests/import`
- `PUT /api/guests/{id}`
- `DELETE /api/guests/{id}`

## Excel Import

Send a `multipart/form-data` request to `POST /api/guests/import` with a `file` field.

Required spreadsheet columns:

- `full_name`
- `company`
- `position`
- `seat_number`

Imported rows are saved into `guests`, and the backend auto-fills the existing required fields (`email`, `suite`, `check_in`, `check_out`, `status`) with import-safe defaults.
