# EventraPlan

A full-stack event planning app: create events, manage a guest list per event, generate a QR code for each guest, and check them in at the door by scanning it.

**Live app:** [eventraplan.netlify.app](https://eventraplan.netlify.app)
**Frontend repo:** [github.com/iris-zaf/taskplan-frontend](https://github.com/iris-zaf/taskplan-frontend)

![Tests](https://github.com/iris-zaf/taskplan/actions/workflows/tests.yml/badge.svg)

## Screenshots

![Homepage](https://raw.githubusercontent.com/iris-zaf/taskplan-frontend/main/docs/screenshots/homepage.png)
![Dashboard](https://raw.githubusercontent.com/iris-zaf/taskplan-frontend/main/docs/screenshots/dashboard.png)

## What it does

- Register/log in, and manage your own events . Every account only ever sees its own data.
- Create, edit, delete, search, filter, and sort events.
- Add guests (name + email) to an event; each guest gets a unique QR code.
- Share a guest's QR code by downloading it as an image, or via a public ticket link (`/ticket/:code`) that needs no login
- Scan guest QR codes from a dedicated check-in page to mark attendance.

## Architecture

```
React (Vite)  --->  Laravel API  --->  MySQL
  Netlify           Render (Docker:        Aiven
                     nginx + PHP-FPM)
```

- **Frontend** : React 19 SPA, deployed on Netlify.
- **Backend** :  Laravel API, deployed on Render. It runs behind a real web server setup (not Laravel's simple built-in one, which can only handle one visitor at a time and would fail under real traffic).- **Database** — MySQL hosted on Aiven.

## Tech stack

**Backend:** Laravel 13, PHP 8.3, Laravel Sanctum (token auth), MySQL, PHPUnit
**Frontend:** React 19, Vite, React Router, Bootstrap 5, Framer Motion, SweetAlert2, `qrcode.react`, `html5-qrcode`
**Infrastructure:** Render (API hosting), Netlify (frontend hosting), Aiven (the MySQL db), GitHub Actions (CI)

## API overview

All routes are prefixed `/api`. Everything except register/login/the public ticket lookup requires a `Authorization: Bearer <token>` header (Sanctum).

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/register`, `/login` | Create an account / get a token |
| POST | `/logout` | Revoke the current token |
| GET | `/user` | Current authenticated user |
| GET/POST | `/events` | List / create your events |
| GET/PUT/DELETE | `/events/{event}` | View, update, or delete one of your events (404 if it isn't yours) |
| GET/POST | `/events/{event}/guests` | List / add guests for one of your events |
| DELETE | `/events/{event}/guests/{guest}` | Remove a guest |
| POST | `/guests/checkin` | Look up a guest by their QR code and mark them checked in (idempotent) |
| GET | `/guests/ticket/{code}` | Public, no auth — a guest's own ticket info, used by the shareable ticket link |

## Testing

The test suite checks two things: that login/signup work correctly, and — more importantly — that a user can only ever see or change their *own* events and guests, never someone else's.

\`\`\`bash
php artisan test
\`\`\`

Tests run against a temporary, throwaway database that resets every time, so they never touch real data. They also run automatically on GitHub every time code is pushed (see the green checkmark badge above).
## Local development setup requirements

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

The frontend (another repo) expects the API at `http://127.0.0.1:8000/api` by default — see its README for setup.
