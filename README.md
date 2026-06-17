# FitMind Backend API

FitMind Backend API is the Laravel REST API for the FitMind gym management and fitness platform. It serves the admin, coach, and member workflows used by the FitMind web dashboard, mobile application, and AI service.

The backend manages gym members, subscriptions, membership plans, profiles, goals, nutrition, workouts, injuries, news, feedback, coach sessions, notifications, and AI-assisted training and nutrition plan workflows.

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [API Overview](#api-overview)
- [Authentication](#authentication)
- [Installation](#installation)
- [Docker](#docker)
- [Environment Variables](#environment-variables)
- [Queues and Scheduler](#queues-and-scheduler)
- [Testing and API Clients](#testing-and-api-clients)
- [Service Integrations](#service-integrations)
- [Known Notes](#known-notes)
- [Graduation Project](#graduation-project)
- [Authors and License](#authors-and-license)

## Features

- Authentication with Laravel Sanctum access tokens.
- Login, logout, password change, forgot password, OTP verification, and reset-token password reset flow.
- Role-aware account creation based on role IDs used in the services:
  - `1`: manager
  - `2`: admin
  - `3`: coach
  - `4`: member/user
- Subscription access middleware for protected member routes using `auth:sanctum` and `check.sub`.
- Member management with overview and nutrition profile endpoints.
- Subscription renewal, freeze, resume, expiration handling, remaining-day calculations, and subscription notifications.
- Membership plan CRUD.
- Profile and user goal management.
- Dashboard statistics for members, active subscriptions, equipment reports, revenue, recent issues, and subscription trends.
- Coach listing and coach session scheduling.
- Session booking, cancellation, admin cancellation/restore, capacity tracking, and session reminder notifications.
- General exercise categories and exercise CRUD.
- User program, program version, and program exercise data structures for training plans.
- General nutrition categories and food CRUD with calories, protein, carbs, fat, badge, image, and serving size fields.
- User nutrition plans, nutrition versions, and nutrition food item management.
- Food preference relationships for liked and disliked foods.
- User injury management with injury dashboard data.
- Injury-triggered AI training modification request creation when an active training plan exists.
- News management with public/draft/deleted statuses, published and expiration dates, public news feed, stats, queued email sending, and news notifications.
- Feedback system for equipment reports, suggestions, and trainer ratings.
- In-app notifications with unread/read state, deduplication keys, priorities, entity metadata, and channels.
- Expo push notification support through stored mobile push tokens.
- AI integration endpoints for chat, syncing/searching data, generating training plans, generating nutrition plans, modifying plans, and analyzing progress.
- Pending training and nutrition plan approval flows for admin/coach review.
- Modification request APIs for training and nutrition plan changes.

## Tech Stack

| Area | Technology |
| --- | --- |
| Backend framework | Laravel Framework `12.53.0` |
| PHP requirement | `^8.2` from `composer.json` |
| Auth | Laravel Sanctum `4.3.1` |
| Database | MySQL/MariaDB supported by config and Docker PHP extensions; SQLite is used by the example env/testing config |
| Queue | Laravel database queue by default |
| Scheduler | Laravel scheduler in `routes/console.php` |
| Mail | Laravel Mail with queued mail jobs |
| Push notifications | Expo Push API via `ExpoNotificationService` |
| AI service | External Python/FastAPI-style service configured by `PYTHON_AI_URL` |
| Docker | `php:8.2-fpm` Dockerfile plus Nginx config |
| Composer | Composer project with Laravel scripts |
| Tests | PHPUnit `11.5.x` |
| Frontend assets | Vite/Tailwind dependencies are present, but this repository is backend-focused |

## Project Structure

| Path | Purpose |
| --- | --- |
| `routes/api.php` | Main REST API route definitions and middleware grouping |
| `routes/console.php` | Scheduled commands for subscriptions and session reminders |
| `app/Http/Controllers` | API controllers for auth, members, plans, AI, news, feedback, sessions, notifications, and more |
| `app/Http/Controllers/api` | Auth controller namespace |
| `app/Http/Requests` | Form request validation classes |
| `app/Http/Middleware` | Custom middleware, including subscription access checks |
| `app/Services` | Business logic for auth, members, subscriptions, AI, plans, nutrition, notifications, feedback, sessions, and news |
| `app/Models` | Eloquent models for users, roles, subscriptions, plans, exercises, nutrition, notifications, feedback, sessions, and AI workflow data |
| `app/Console/Commands` | Scheduled command classes |
| `app/Jobs` | Queued mail and push notification jobs |
| `app/Mail` | Mailables for registration, OTP, and news emails |
| `database/migrations` | Schema definitions for users, roles, subscriptions, plans, nutrition, exercises, notifications, feedback, sessions, bookings, and supporting tables |
| `database/seeders` | Laravel seeder entry point |
| `config` | Laravel app, auth, Sanctum, queue, mail, database, services, and filesystem configuration |
| `docker` | Nginx runtime configuration |
| `resources/views/emails` | Email templates |
| `tests` | PHPUnit unit and feature test folders |

## API Overview

Base path: `/api`

| Group | Representative endpoints | Description |
| --- | --- | --- |
| Auth | `POST /login`, `POST /logout`, `POST /register`, `POST /change-password`, `POST /forgot-password`, `POST /verify-otp`, `POST /reset-password` | Token auth, account creation, password update/reset |
| Current user | `GET /user` | Returns the authenticated user |
| Notifications | `GET /notifications`, `GET /notifications/unread-count`, `POST /notifications/{id}/read`, `POST /notifications/read-all`, `POST /save-token` | In-app notifications and mobile push token registration |
| Profiles | `POST /profile`, `GET /profile/user/{userId}`, `PUT /profile/user/{userId}`, `DELETE /profile/user/{userId}` | Member profile data |
| Members | `GET /members`, `GET /members/{id}`, `GET /members/overView/{id}`, `GET /members/nutrition/{id}`, `POST /members` | Member listing, detail, overview, nutrition context, creation |
| Subscriptions | `POST /members/ReNewSubscription`, `POST /members/subscription/freeze/{id}`, `POST /members/subscription/resume/{id}`, `GET /subscriptionForAdmin` | Subscription lifecycle and admin subscription listing |
| Coaches and sessions | `GET /coaches`, `POST /coach/session`, `GET /sessions`, `GET /my-sessions`, `POST /sessions/{id}/book`, `DELETE /sessions/{id}/cancel`, `GET /admin/sessions` | Coach discovery, session scheduling, bookings, admin session management |
| Dashboard | `GET /dashboard` | Admin dashboard statistics |
| Plans | `GET /plans`, `POST /plans`, `GET /plans/{id}`, `PUT /plans/{id}`, `DELETE /plans/{id}` | Membership plan CRUD |
| Exercises | `GET /generalExercise`, `POST /generalExercise`, `GET /generalExercise/{id}/exercises`, `POST /exercises`, `PUT /exercises/{id}` | Exercise categories and exercise management |
| Nutrition and foods | `GET /generalNutrition`, `GET /general-nutrition`, `apiResource /foods` | Nutrition categories and food catalog |
| User goals | `apiResource /user-goals` | Member fitness goals |
| Injuries | `apiResource-like /userInjuries`, `GET /userInjuries/dashboard` | User injuries and injury dashboard |
| Feedback | `GET /feedback/dashboard`, `POST /feedback`, `GET /my-feedback`, `GET/PUT/DELETE /feedback/{id}` | Equipment reports, suggestions, and trainer ratings |
| News | `GET /news`, `GET /news/public`, `GET /news/stats`, `POST /news`, `PUT /news/{id}`, `DELETE /news/{id}` | News publishing and public feed |
| Training plans | `apiResource /user-programs`, `apiResource /program-versions` | User training programs and versioned training plans |
| Nutrition plans | `apiResource /user-nutrition-plans`, `apiResource /nutrition-versions`, `apiResource /nutrition-food-items` | User nutrition plans, nutrition versions, and meal food items |
| Pending plan approval | `GET/POST /PendingTrainingPlans`, `GET/POST /PendingNutritionPlans` | Review and save edited AI-generated or pending plans |
| Modification requests | `apiResource /modification-requests`, `GET /modification-requests/training`, `GET /modification-requests/nutrition`, approval endpoints | Training and nutrition modification request workflow |
| AI | `POST /ai/chat`, `POST /sync-all`, `POST /search-exercises`, `POST /search-foods`, `POST /generate-training-plan`, `POST /modify-training-plan`, `POST /generate-nutrition-plan`, `POST /modify-nutrition-plan`, `POST /analyze-progress` | Laravel-to-Python AI service integration |

## Authentication

The API uses Laravel Sanctum bearer tokens.

1. Login:

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

2. Copy the returned `token`.

3. Send authenticated requests with:

```http
Authorization: Bearer <token>
Accept: application/json
```

Member-protected routes use the custom `check.sub` middleware. For role `4` users, the middleware checks the latest subscription and blocks access when the subscription is missing, expired, cancelled, or otherwise inactive. Frozen subscriptions with remaining days can be resumed by the service logic.

## Installation

### Requirements

- PHP `8.2` or newer
- Composer
- MySQL/MariaDB for full application behavior
- Node.js and npm only if you need the bundled Vite assets

### Local setup

```bash
git clone <repository-url>
cd gym
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` for your local database, mailer, queue, and AI service. Do not commit real secrets.

Run migrations:

```bash
php artisan migrate
```

Optional seeding:

```bash
php artisan db:seed
```

The current `DatabaseSeeder` is minimal. For a fresh FitMind environment, create or seed the required roles and domain data expected by the service layer before relying on role-based flows.

Start the API:

```bash
php artisan serve
```

Default local API URL:

```text
http://127.0.0.1:8000/api
```

Optional frontend asset build:

```bash
npm install
npm run build
```

## Docker

Docker files are present, but this repository does not include a `docker-compose.yml`.

Included Docker assets:

| File | Purpose |
| --- | --- |
| `Dockerfile` | Builds a PHP `8.2-fpm` backend image, installs common PHP extensions including `pdo_mysql`, and runs `php-fpm` on port `9000` |
| `docker/nginx/default.conf` | Nginx config that serves `public/`, listens on port `80`, and forwards PHP requests to `backend:9000` |

Build the backend image:

```bash
docker build -t fitmind-backend .
```

If this backend is run from a parent FitMind Docker Compose stack, that stack should define at least:

- a PHP-FPM service named `backend`
- an Nginx service using `docker/nginx/default.conf`
- a database service such as MySQL/MariaDB
- the external AI service reachable by `PYTHON_AI_URL`

In such a stack, useful commands are typically:

```bash
docker compose up -d
docker compose exec backend php artisan migrate
docker compose exec backend php artisan queue:work
docker compose exec backend php artisan schedule:work
```

No database container or database port is defined directly in this repository. Laravel's MySQL default port is `3306` when `DB_PORT` is not overridden.

## Environment Variables

Use `.env.example` as the template. Configure categories rather than committing real values.

| Category | Examples |
| --- | --- |
| Application | `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` |
| Database | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Queue | `QUEUE_CONNECTION`, database queue table settings |
| Cache/session | `CACHE_STORE`, `SESSION_DRIVER`, Redis settings if used |
| Mail | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, sender address/name |
| Sanctum | `SANCTUM_STATEFUL_DOMAINS`, token prefix if needed |
| AI service | `PYTHON_AI_URL` |
| Push notifications | Expo push tokens are stored through `/api/save-token`; the current push sender posts to Expo's push endpoint |
| Optional Laravel services | AWS, Slack, Postmark, Resend, SES values if those drivers are used |

## Queues and Scheduler

The application uses queued jobs for:

- registration emails
- OTP emails
- news emails
- Expo push notifications

Run a queue worker in development or production:

```bash
php artisan queue:work
```

Scheduled commands are registered in `routes/console.php`:

| Schedule | Command | Purpose |
| --- | --- | --- |
| Daily | `subscriptions:close-expired` | Marks expired subscriptions, syncs user subscription state, and creates expiration notifications |
| Every five minutes | `notifications:send-session-reminders` | Sends reminders for booked coach sessions starting soon |

Run the scheduler locally:

```bash
php artisan schedule:work
```

Production deployments usually run Laravel's scheduler through cron:

```bash
* * * * * cd /path/to/gym && php artisan schedule:run >> /dev/null 2>&1
```

## Testing and API Clients

Run the test suite:

```bash
composer test
```

or:

```bash
php artisan test
```

The current `tests` folder contains starter PHPUnit unit and feature examples.

No Postman, Insomnia, OpenAPI, or Swagger collection was found in this repository. To test protected endpoints manually:

1. Call `POST /api/login`.
2. Copy the returned Sanctum token.
3. Add `Authorization: Bearer <token>` to subsequent requests.
4. Use `Accept: application/json` for API responses.

## Service Integrations

FitMind Backend API is designed to sit between the FitMind clients and supporting services:

| Service | Integration |
| --- | --- |
| React web dashboard | Consumes admin, coach, member, plan, dashboard, news, feedback, and approval endpoints |
| React Native mobile app | Consumes member, profile, goals, nutrition, training, injury, session, notification, and push-token endpoints |
| FastAPI AI service | Receives requests from `AIController` and `TrainingPlanModificationService` through `PYTHON_AI_URL` |
| Expo Push API | Receives push messages from queued notification jobs |

## Known Notes

- Route protection is mixed. Many member/mobile routes use `auth:sanctum` plus `check.sub`, while some catalog/admin/AI routes are currently defined without route middleware or rely on service/controller checks. Review authorization before production deployment.
- `POST /api/ai/generate` is registered in `routes/api.php` as `AIController@generateProgram`, but that method is not present. Use the implemented `generate-training-plan` and `generate-nutrition-plan` endpoints.
- This backend repository includes a Dockerfile and Nginx config, but no Docker Compose file.
- No API collection or OpenAPI specification is included.
- Some migrations define tables that do not currently have active API controllers/routes, such as offers, check-ins, progress logs, workout logs, meal logs, nutrition logs, and AI interactions.
- MySQL/MariaDB is recommended for full behavior because parts of the service layer use MySQL-style date expressions. The example environment and PHPUnit config still use SQLite defaults.

## Graduation Project

This backend is part of the FitMind graduation project, an AI-powered gym management and fitness platform connecting gym administration, coaches, members, mobile experiences, and AI-assisted fitness planning.

## Authors and License

Authors:

- FitMind Graduation Project Team
- Add team member names here

License:

No dedicated `LICENSE` file was found in this repository. Add a project license before public distribution.
