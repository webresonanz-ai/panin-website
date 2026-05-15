# Luxury Guest Dashboard Backend

Minimal PHP 8 + MySQL API for the Vue frontend.

## Features

- Token-based authentication middleware for protected routes
- Guest CRUD endpoints matching the current frontend fields
- Excel guest import endpoint for `.xlsx` and `.csv` files
- MySQL schema with a seeded admin user and guest records
- Lightweight custom router/container with no Composer dependency

## Setup

1. Update the database credentials in `backend/.env`
2. Install backend dependencies:

```bash
cd backend
composer install
```

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
- `GET /api/guests/{id}/invitation-ticket`
- `POST /api/guests/send-pending-invitations`
- `POST /api/guests/check-pending-wasender-statuses`
- `POST /api/guests`
- `POST /api/guests/import`
- `PUT /api/guests/{id}`
- `DELETE /api/guests/{id}`

## Invitation Ticket PDF

Use `GET /api/guests/{id}/invitation-ticket` to generate a PDF invitation ticket for a guest. The endpoint returns `application/pdf` inline and uses the shared invitation template at `backend/templates/panin_invitation.png`.

Use `POST /api/guests/send-pending-invitations` to send WhatsApp invitations only for guests whose `wa_sent_time` is still `NULL` or empty. Guests with an existing `wa_sent_time` are skipped automatically.

Use `POST /api/guests/check-pending-wasender-statuses` to query Wasender by each saved `wasender_msgId` for guests whose `wasender_status` is still `pending`, then update the local `wasender_status` field with the latest status.

Required runtime support:

- Composer dependencies from `backend/composer.json`
- PHP GD extension enabled

## Excel Import

Send a `multipart/form-data` request to `POST /api/guests/import` with a `file` field.

Required spreadsheet columns:

- `full_name`
- `company`
- `position`
- `seat_number`

Imported rows are saved into `guests`, and the backend auto-fills the remaining required stay fields (`check_in`, `check_out`, `status`) with import-safe defaults.
