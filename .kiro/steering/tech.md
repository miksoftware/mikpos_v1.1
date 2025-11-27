# MikPOS - Tech Stack

## Backend
- PHP 8.2+
- Laravel 12.x
- Livewire 3.x (full-page components with attributes)
- SQLite (default, configurable)

## Frontend
- Tailwind CSS 4.x (via Vite plugin)
- Alpine.js (bundled with Livewire)
- Vite 7.x for asset bundling
- Inter font family

## Key Dependencies
- `livewire/livewire` - Reactive UI components
- `laravel/pint` - Code formatting
- `phpunit/phpunit` - Testing

## Common Commands

All commands run from the workspace root:

```bash
# Initial setup
composer setup

# Development (starts server, queue, logs, vite concurrently)
composer dev

# Run tests
composer test

# Build assets for production
npm run build

# Run migrations
php artisan migrate

# Code formatting
./vendor/bin/pint
```

## Database
- Default: SQLite at `database/database.sqlite`
- Migrations in `database/migrations/`
- Uses Laravel's migration naming convention with timestamps
