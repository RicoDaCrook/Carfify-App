# Carfify v4.0 - Enterprise Autopflege WebApp

## Features
- ✅ Moderne React Frontend mit Tailwind CSS
- ✅ Vollständige Authentifizierung (Login/Register)
- ✅ Buchungssystem mit Kalender
- ✅ Admin Dashboard
- ✅ Responsive Design
- ✅ PHP Backend API
- ✅ MySQL Datenbank

## Installation

### Frontend Setup
```bash
cd frontend
npm install
npm run dev
```

### Backend Setup
1. MySQL Datenbank importieren: `database/schema.sql`
2. PHP Server starten (z.B. XAMPP/MAMP)
3. API Endpoints unter `/api/`

### Datenbank Konfiguration
- Datenbank: `carfify`
- Benutzer: `root` (anpassen in `api/config/database.php`)
- Passwort: '' (anpassen)

## API Endpoints
- POST `/api/auth/login.php`
- POST `/api/auth/register.php`
- GET `/api/services/get.php`
- POST `/api/bookings/create.php`
- GET `/api/bookings/user.php`

## Default Login
- Admin: admin@carfify.com / admin123

## Tech Stack
- Frontend: React, Tailwind CSS, Vite
- Backend: PHP, MySQL
- Styling: Tailwind CSS
- Icons: Font Awesome