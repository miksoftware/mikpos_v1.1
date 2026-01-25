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
- `livewire/livewire` ^3.6 - Reactive UI components
- `laravel/pint` ^1.24 - Code formatting
- `laravel/tinker` ^2.10.1 - Interactive shell
- `laravel/sail` ^1.41 - Docker development environment
- `laravel/pail` ^1.2.2 - Log viewer
- `nunomaduro/collision` ^8.6 - Error reporting

## Architecture
- **MVC Pattern**: Models for data, Livewire components for controllers/views
- **Service Layer**: `ActivityLogService` for centralized logging
- **Permission System**: Role-based access control with granular permissions
- **Multi-tenancy**: Branch-based user assignment and access control

## Database Schema
- **Core Tables**: users, roles, permissions, modules, branches, activity_logs
- **Geographic**: departments, municipalities
- **Configuration**: tax_documents, currencies, payment_methods, taxes
- **Product Catalog**: categories, subcategories, brands, units, product_models, presentations, colors, imeis
- **Relationships**: Proper foreign key constraints and cascading deletes

## Common Commands

All commands run from the workspace root:

```bash
# Initial setup (installs dependencies, generates key, runs migrations, builds assets)
composer setup

# Development (starts server, queue, logs, vite concurrently)
composer dev

# Build assets for production
npm run build

# Run migrations
php artisan migrate

# Fresh migration with seeders
php artisan migrate:fresh --seed

# Code formatting
./vendor/bin/pint

# Interactive shell
php artisan tinker

# View logs
php artisan pail
```

## Development Workflow
1. **Database Changes**: Create migrations with proper naming convention
2. **New Features**: Create Livewire component + model + view + permissions
3. **Permissions**: Add to seeder and assign to roles
4. **Routes**: Register in `routes/web.php` with permission middleware
5. **Menu**: Update `resources/views/layouts/app.blade.php` sidebar
6. **Validation**: Use default test users (admin@mikpos.com/password)

## Database
- Default: SQLite at `database/database.sqlite`
- Migrations in `database/migrations/` with timestamp naming
- Seeders create default data including test users and permissions
- Activity logging tracks all CRUD operations with old/new values

## UI/UX Standards
- **Design System**: Consistent Tailwind classes across components
- **Color Scheme**: Gradient from #ff7261 to #a855f7 for primary actions
- **Icons**: Heroicons for consistent iconography
- **Modals**: Inline modals with backdrop blur and animations
- **Notifications**: Toast system with success/error/warning/info types
- **Responsive**: Mobile-first design with collapsible sidebar
