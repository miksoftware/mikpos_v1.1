# MikPOS - Project Structure

Root application is in the workspace root directory.

## Key Directories

```
/
├── app/
│   ├── Http/Controllers/    # Traditional controllers (minimal use)
│   ├── Livewire/            # Livewire components (primary UI logic)
│   │   └── Auth/            # Authentication components
│   ├── Models/              # Eloquent models
│   ├── Providers/           # Service providers
│   └── View/Components/     # Blade view components
├── config/                  # Laravel configuration files
├── database/
│   ├── factories/           # Model factories for testing
│   ├── migrations/          # Database migrations
│   └── seeders/             # Database seeders
├── resources/
│   ├── css/                 # Tailwind CSS entry point
│   ├── js/                  # JavaScript entry point
│   └── views/
│       ├── components/      # Reusable Blade components
│       ├── layouts/         # Layout templates (app.blade.php, guest.blade.php)
│       └── livewire/        # Livewire component views
├── routes/
│   └── web.php              # Web routes (Livewire components as routes)
└── tests/
    ├── Feature/             # Feature tests
    └── Unit/                # Unit tests
```

## Conventions

### Livewire Components
- Full-page components use `#[Layout('layouts.app')]` attribute
- Guest pages use `#[Layout('layouts.guest')]` attribute
- Views in `resources/views/livewire/` mirror component namespace
- Use Livewire attributes for validation: `#[Rule('required|min:3')]`

### Models
- Located in `app/Models/`
- Use `$fillable` for mass assignment protection
- Use `$casts` for attribute casting
- Define relationships as methods

### Views
- Blade templates with Tailwind CSS classes
- Modals rendered inline within Livewire components
- Use `wire:` directives for Livewire bindings
- Use `x-data`, `x-show`, `@click` for Alpine.js interactivity

### Notifications
- Use `$this->dispatch('notify', message: 'Message', type: 'success')` in Livewire
- Types: success, error, warning, info
- Toast component in `resources/views/components/toast.blade.php`

### Activity Logging
- Use `ActivityLogService::logCreate/logUpdate/logDelete()` for CRUD operations
- Logs stored in `activity_logs` table with old/new values

### Routing
- Livewire components registered directly as routes
- Protected routes use `auth` middleware
- Guest routes use `guest` middleware
