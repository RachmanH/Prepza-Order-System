<div align="center">

# Prepza

### Voice-First F&B Ordering & Queue Management System

A deterministic Layer 1 backend for AI-assisted, Indonesian-language self-service food ordering — pairing rule-based parsing with Groq AI fallback, real-time queue operations, and tight integration with an external Layer 2 service that owns kitchen processing state.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red?logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://img.shields.io/badge/build-pending-lightgrey?logo=github)](../../actions)
[![Livewire](https://img.shields.io/badge/Livewire-3.6-fb7092?logo=livewire&logoColor=white)](https://livewire.laravel.com/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-CSS-38BDF8?logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)

</div>

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Architecture](#architecture)
- [Domain Models](#domain-models)
- [Getting Started](#getting-started)
- [Configuration](#configuration)
- [API Reference](#api-reference)
- [UI Screens](#ui-screens)
- [Screenshots](#screenshots)
- [Roadmap](#roadmap)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)
- [Acknowledgements](#acknowledgements)

---

## Overview

**Prepza** is a voice-driven F&B ordering and queue management system designed for Indonesian warung/eatery settings. It acts as the deterministic **Layer 1** backbone in a two-system architecture:

- **Layer 1 (this project)** — owns customer input parsing, order validation, order/queue persistence, and cashier operations.
- **Layer 2 (external)** — owns kitchen/processing state and pushes trending-food content back to Layer 1 for display.

Customers speak or type orders in colloquial Indonesian (e.g. *"saya mau nasgor sama dua teh anget, tambah mie goreng"*). Layer 1 normalizes and parses the input through a hybrid pipeline (rule-based + Groq LLM fallback), validates each item against the active menu, and produces a `valid | partial | invalid` result so the kiosk UI can ask for confirmation when needed.

### Why Layer 1 is "deterministic by design"

AI (Groq) is used only for two purposes:
1. **Speech-to-Text** (Whisper) — transcribing customer audio.
2. **NLP fallback** — parsing text only when the rule-based parser fails or as a merge source.

The core engine (normalization, validation, order creation, queue management, event dispatch) is fully deterministic — AI output is never trusted raw and always goes through the same validation pipeline.

---

## Key Features

- **Indonesian voice/text order intake** with colloquial normalization (slang, number-words, possessive `-nya` suffix, segment splitting).
- **Hybrid parsing pipeline**: rule-based → Groq LLM merge → deterministic validation (`exact → alias → composite phrase → repeated-name → fuzzy ≥80% → Groq semantic match`).
- **Confidence-aware ordering**: returns `valid | partial | invalid` so the UI can request confirmation/clarification.
- **Auto order codes** in the form `ORD-<ULID>` with atomic DB transactions.
- **Event-driven Layer 2 sync** via queued listener (`SendOrderToLayer2`) + fire-and-forget status notifications.
- **Single-active-processing guarantee**: only one order may be `processing` per day; Layer 2-driven promotion demotes others back to `waiting`.
- **Public queue board** with current processing, upcoming queue, recent done, and a **trending-food carousel** (gender-targeted: male/female/all, auto-detected from title tokens).
- **Cashier operations**: cancel, append voice/text items to a live order, edit item qty/note, remove item.
- **Super-admin menu management** with category CRUD, menu CRUD, conversational aliases, and image upload (local disk) or external URL.
- **Jetstream + Sanctum + Fortify** auth with API tokens, 2FA, and profile management.
- **Bilingual UI**: Tailwind-redesigned Jetstream screens with Alpine.js reactivity.

---

## Architecture

```
┌────────────────────────────────────────────────────────────────────┐
│                              LAYER 1 (Prepza)                       │
│                          Laravel 12 + Jetstream                     │
│                                                                     │
│  ┌──────────┐   ┌──────────────┐   ┌──────────────┐   ┌─────────┐ │
│  │  Kiosk   │──▶│ Voice/Text   │──▶│  Parse +     │──▶│ Order   │ │
│  │  UI      │   │ Input        │   │  Validate    │   │ Create  │ │
│  │ (Blade)  │   │ (Groq STT)   │   │ (Hybrid AI)  │   │ + Queue │ │
│  └──────────┘   └──────────────┘   └──────────────┘   └────┬────┘ │
│                                                            │      │
│  ┌──────────────┐   ┌──────────────┐   ┌─────────────┐    │      │
│  │ Cashier UI   │   │ Queue Board  │   │ Menu Mgmt   │    │      │
│  │ (cancel,     │   │ (display +   │   │ (super-admin│    │      │
│  │  append,     │   │  trending)   │   │  CRUD)      │    │      │
│  │  edit items) │   │              │   │             │    │      │
│  └──────┬───────┘   └──────────────┘   └─────────────┘    │      │
│         │                                               │      │
│         └─────── REST/JSON (Sanctum) ────────────────────┘      │
│                              │                                    │
└──────────────────────────────┼────────────────────────────────────┘
                               │
                  ┌────────────▼────────────┐
                  │   OrderCreated Event    │
                  │   (queued listener)     │
                  └────────────┬────────────┘
                               │ POST /api/orders/incoming
                               ▼
┌────────────────────────────────────────────────────────────────────┐
│                              LAYER 2 (External)                     │
│           Owns kitchen/processing state & trending content          │
│                                                                     │
│   - Promotes order to processing/done                                │
│   - Calls PATCH /api/queue/orders/{order}/external-update           │
│   - Pushes trends via POST /api/queue/trends/update                 │
└────────────────────────────────────────────────────────────────────┘
```

### Voice Order Flow

```
Customer speech
   │
   ▼
Groq Whisper STT ──▶ raw text
   │
   ▼
normalizeText() ──▶ strip slang, expand number-words, drop possessive -nya
   │
   ▼
parseByRules() ──┐
                 ├─ merge (prefer higher qty) ──▶ candidate items
Groq LLM parse ──┘
   │
   ▼
validateItems()
   ├─ exact menu name match
   ├─ MenuAlias index lookup
   ├─ composite phrase resolver
   ├─ repeated-name resolver
   ├─ fuzzy match (similar_text ≥ 80%)
   └─ Groq matchMenuCandidate (semantic)
   │
   ▼
validation_status: valid | partial | invalid
   │
   ▼ (on confirm)
DB transaction: Order + OrderItems + OrderQueue
   │
   ▼
OrderCreated event ──▶ SendOrderToLayer2 (queued, 3 tries, 10s backoff)
```

### Tech Stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 12, PHP 8.2 |
| Auth | Jetstream (Livewire stack) + Sanctum + Fortify |
| Frontend | Blade + Tailwind CSS + Alpine.js + Vite |
| DB | SQLite (default) / MySQL / MariaDB |
| Queue | Laravel Queue (database driver) |
| AI | Groq — Whisper STT + `openai/gpt-oss-20b` validation model |
| Realtime | Polling-based (Layer 2 push via REST) |

---

## Domain Models

Eight Eloquent models power the system:

| Model | Purpose | Key Relationships |
| --- | --- | --- |
| `User` | Auth user (admin / cashier / super admin) | — |
| `Category` | Menu grouping (Makanan Berat, Minuman, etc.) | `hasMany Menu` |
| `Menu` | A food/drink item with price & image | `belongsTo Category`, `hasMany MenuAlias`, `hasMany OrderItem` |
| `MenuAlias` | Spoken/colloquial alias for a menu (e.g. "nasgor" → "nasi goreng") | `belongsTo Menu` |
| `Order` | A single customer order (auto `ORD-<ULID>` code) | `hasMany OrderItem`, `hasOne OrderQueue` |
| `OrderItem` | Line item linked to a menu snapshot | `belongsTo Order`, `belongsTo Menu` |
| `OrderQueue` | 1:1 queue slot (`queue_number` as PK) | `belongsTo Order` |
| `QueueTrend` | Trending-food carousel content pushed from Layer 2 | — |

> See `app/Models/` for full source and `database/migrations/` for schema details.

---

## Getting Started

### Prerequisites

- **PHP** 8.2 or higher
- **Composer** 2.x
- **Node.js** 18+ and **npm**
- **SQLite** (default) or **MySQL/MariaDB**
- A **Groq API key** — get one at <https://console.groq.com>
- (Optional) A running **Layer 2** service to receive order pushes

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/<your-username>/MamenProjFix.git
cd MamenProjFix

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies & build assets
npm install
npm run build

# 4. Environment setup
cp .env.example .env
php artisan key:generate

# 5. Configure your Groq & Layer 2 keys in .env  (see Configuration)

# 6. Run migrations & seed the demo menu
php artisan migrate --seed
```

### Running the Development Server

The project ships with a concurrent dev command that runs the web server, queue worker, log tail, and Vite together:

```bash
composer dev
```

This starts four processes in parallel:
- `php artisan serve` — web server
- `php artisan queue:listen` — queue worker (needed for Layer 2 sync)
- `php artisan pail` — live log tail
- `npm run dev` — Vite HMR

Visit <http://localhost:8000> and log in with the seeded super-admin:

| Email | Password |
| --- | --- |
| `test@example.com` | `password` |

---

## Configuration

All project-specific configuration lives in `.env`. The relevant variables (beyond standard Laravel keys):

| Variable | Description | Default |
| --- | --- | --- |
| `GROQ_API_KEY` | **Required.** Your Groq API key. | — |
| `GROQ_BASE_URL` | Groq API base URL. | `https://api.groq.com/openai/v1` |
| `GROQ_MODEL` | Validation/parsing LLM model. | `openai/gpt-oss-20b` |
| `GROQ_STT_MODEL` | Speech-to-text model. | `whisper-large-v3-turbo` |
| `LAYER2_ENDPOINT_BASE_URL` | Base URL of your Layer 2 service. | `http://127.0.0.1:8002` |
| `LAYER2_INCOMING_ORDER_PATH` | Path Layer 2 exposes for incoming orders. | `/api/orders/incoming` |

Config source: `config/services.php` (`groq` and `layer2` keys).

> **Note:** The queue worker (`php artisan queue:listen`) is required for Layer 2 sync — `composer dev` already starts it. If you run `php artisan serve` standalone, also run the queue worker in a separate terminal.

---

## API Reference

The JSON API lives under `/api` and is consumed both by the Blade UI (via `fetch()`) and by Layer 2. Full request/response samples, status code matrix, and integration notes are in [`dokumentasiapi.md`](./dokumentasiapi.md).

### Public Endpoints (no auth)

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/categories` | List active categories |
| `GET` | `/api/menus` | List active menus with aliases & category |
| `POST` | `/api/menus/resolve` | Resolve free-text candidate to a menu |
| `POST` | `/api/orders/voice` | Create order from text or structured items |
| `POST` | `/api/orders/voice/preview` | Dry-run analysis without persisting |
| `POST` | `/api/orders/voice/transcribe` | Audio → Groq STT (optional `auto_order`) |
| `GET` | `/api/queue/orders` | List orders (filterable by status) |
| `GET` | `/api/queue/board` | Queue board payload (current, upcoming, done, trends) |
| `POST` | `/api/queue/trends/update` | Upsert a `QueueTrend` (called by Layer 2) |
| `PATCH` | `/api/queue/orders/{order}/cancel` | Cancel an order |
| `PATCH` | `/api/queue/orders/{order}/external-update` | Layer 2 updates processing status |
| `POST` | `/api/queue/orders/{order}/append-voice` | Append items to a live order |
| `PATCH` | `/api/queue/orders/{order}/items/{item}` | Edit an order item (qty/note) |
| `DELETE` | `/api/queue/orders/{order}/items/{item}` | Remove an order item |

> `PATCH /api/queue/orders/{order}/start` and `/finish` exist but **intentionally return 403** — only Layer 2 controls processing transitions.

### Admin Endpoints (`auth:sanctum` + `super_admin` middleware)

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET/POST/PUT/PATCH/DELETE` | `/api/admin/categories` | Category CRUD |
| `GET/POST/PUT/PATCH/DELETE` | `/api/admin/menus` | Menu CRUD (with image upload) |
| `POST` | `/api/admin/menus/{menu}/remove-image` | Remove a menu's stored image |
| `PATCH` | `/api/admin/menus/{menu}/toggle` | Toggle `is_active` |

### Authenticated Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/user` | Current authenticated user (`auth:sanctum`) |

---

## UI Screens

| Route | Purpose | Access |
| --- | --- | --- |
| `/` | Landing page | Public |
| `/dashboard` | Today's order stats | Authenticated |
| `/order-kiosk` | Customer-facing voice/text ordering kiosk | Authenticated |
| `/queue-management` | Cashier queue operations | Authenticated |
| `/queue-board` | Public display board (current + upcoming + trends) | Public |
| `/cashier-panel` | Cashier operations (redirects to `/queue-management`) | Authenticated |
| `/menu-management` | Super-admin menu/category CRUD | Super admin |
| `/login`, `/register`, `/profile`, `/api-tokens` | Jetstream auth & profile | Per Jetstream |

---

## Screenshots

> _Screenshots coming soon._ Drop image files into `public/screenshots/` and uncomment the blocks below.

<!--

### Order Kiosk
![Order Kiosk](public/screenshots/order-kiosk.png)

### Queue Board
![Queue Board](public/screenshots/queue-board.png)

### Queue Management (Cashier)
![Queue Management](public/screenshots/queue-management.png)

### Menu Management (Super Admin)
![Menu Management](public/screenshots/menu-management.png)

### Dashboard
![Dashboard](public/screenshots/dashboard.png)

-->

---

## Roadmap

- [ ] **Test coverage for domain logic** — currently only Jetstream stock tests exist; menu/order/voice/cashier pipelines need feature tests.
- [ ] **Authenticate Layer 2 integration endpoints** — `external-update` and `trends/update` are currently open (no signature/token); add HMAC or shared-secret middleware. (Noted in `dokumentasiapi.md`.)
- [ ] **RBAC via Laravel Policies** — replace the single `is_super_admin` flag with role-based access (e.g. cashier vs. admin vs. super admin).
- [ ] **Relocate Python helper scripts** — `app/rdd_cluster.py` and `app/rdd_dasar.py` sit inside Laravel's `app/` directory but outside its autoload; move to `tools/` or `scripts/`.
- [ ] **Real-time queue updates** — replace polling with WebSocket/SSE for the queue board.
- [ ] **GitHub Actions CI** — run Pint + PHPUnit on every push.
- [ ] **API documentation** — generate OpenAPI/Scribe spec from routes.
- [ ] **Docker dev environment** — provide a `docker-compose.yml` for one-command setup.

---

## Troubleshooting

### `Groq API returns 401 Unauthorized`
- Verify `GROQ_API_KEY` is set in `.env`.
- Confirm the key is valid at <https://console.groq.com/keys>.
- Restart the server after editing `.env`.

### `Groq API returns 429 Too Many Requests`
- You've hit the Groq rate limit. Wait and retry, or upgrade your Groq plan.
- The rule-based parser still works without Groq — orders will fall back to deterministic validation only.

### `Orders are stuck in waiting status`
- Ensure the queue worker is running: `php artisan queue:listen` (or `composer dev`).
- Check that Layer 2 is reachable at `LAYER2_ENDPOINT_BASE_URL`.
- Inspect the failed jobs table: `php artisan tinker` → `DB::table('failed_jobs')->get();`.

### `Cannot start or finish an order from the UI`
- This is **by design**. `PATCH /api/queue/orders/{order}/start` and `/finish` always return `403`. Only Layer 2 may transition an order to `processing` or `done` via `/external-update`.

### `Menu resolve fails for a known item`
- Add a `MenuAlias` for the colloquial name (e.g. "nasgor" → "nasi goreng") via `/menu-management`.
- Verify the menu's `is_active` is `true`.
- Fuzzy match threshold is `≥ 80%` (similar_text); consider adding an alias if customers use a very different spelling.

### `Queue board shows no orders`
- Run the seeder: `php artisan migrate --seed` (seeds 4 categories and 23 menus).
- Create a test order via `POST /api/orders/voice` with body `{"raw_text": "saya mau nasi goreng dan teh manis"}`.

### `Layer 2 not receiving order pushes`
- Confirm `LAYER2_ENDPOINT_BASE_URL` and `LAYER2_INCOMING_ORDER_PATH` in `.env`.
- Check `storage/logs/laravel.log` for `SendOrderToLayer2` listener exceptions.
- Verify the queue worker is processing the `OrderCreated` event.

### `SQLite "no such table" errors`
- Run `php artisan migrate --seed` to create and populate the database.
- If `database/database.sqlite` is missing, run `touch database/database.sqlite` first.

---

## Contributing

Contributions are welcome. Please follow these steps:

1. Fork the repository.
2. Create a feature branch: `git checkout -b feat/my-feature`.
3. Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style. Run Laravel Pint before committing:
   ```bash
   ./vendor/bin/pint
   ```
4. Add or update tests where reasonable (note: domain logic is currently untested — see [Roadmap](#roadmap)).
5. Commit with a clear message and open a Pull Request against `main`.

Please be respectful and constructive in all discussions.

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## Acknowledgements

- [Laravel](https://laravel.com/) — the framework powering this project.
- [Laravel Jetstream](https://jetstream.laravel.com/) — auth, profile, and API token scaffolding.
- [Livewire](https://livewire.laravel.com/) — reactive Blade components.
- [Tailwind CSS](https://tailwindcss.com/) — utility-first styling.
- [Alpine.js](https://alpinejs.dev/) — lightweight reactivity.
- [Groq](https://groq.com/) — fast inference for Whisper STT and LLM parsing fallback.

<div align="center">

Built with care for Indonesian warungs. _Selamat makan!_ 🍜

</div>
