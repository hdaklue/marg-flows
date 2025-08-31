# KluePortal

A sophisticated Laravel 12 application implementing enterprise-level flow and task management with advanced role-based access control (RBAC) and multi-tenant architecture.

## 🏗️ Architecture Overview

KluePortal uses a **triple-database architecture** to separate concerns and optimize performance:

### Database Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   RBAC Database │    │ Business Database│    │ Original Database│
│   (rbac conn)   │    │ (business_db)    │    │  (mysql conn)   │
├─────────────────┤    ├─────────────────┤    ├─────────────────┤
│ • Users         │    │ • Documents     │    │ • Flows         │
│ • Tenants       │    │ • Feedbacks     │    │ • Stages        │
│ • Roles         │    │ • Deliverables  │    │ • Profiles      │
│ • Permissions   │    │ • Versions      │    │ • Side Notes    │
│ • Notifications │    │ • Acknowledgmts │    │ • Jobs          │
│ • Login Logs    │    │                 │    │                 │
│ • Invitations   │    │                 │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Key Features

- **Multi-tenant RBAC** with 1-hour caching (226ms response, 5MB memory)
- **Performance optimized** role management via hdaklue/marg-rbac package
- **Cross-database relationships** with proper connection handling
- **Production-safe** migration commands with environment protection
- **ULIDs** for all primary keys for better performance

## 🚀 Quick Start

### Prerequisites

- PHP 8.3+
- Laravel 12.26+
- MySQL 5.7+
- Redis (for caching and sessions)
- Composer

### Installation

1. **Clone the repository**
```bash
git clone <repository-url> klueportal
cd klueportal
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure environment variables**
```env
# Main Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=klueportal
DB_USERNAME=root
DB_PASSWORD=

# Business Database  
FEEDBACK_DB_HOST=127.0.0.1
FEEDBACK_DB_PORT=3306
FEEDBACK_DB_DATABASE=klue_portal_business
FEEDBACK_DB_USERNAME=root
FEEDBACK_DB_PASSWORD=

# RBAC Database Configuration
RBAC_DB_CONNECTION=rbac
RBAC_DB_DRIVER=mysql
RBAC_DB_HOST=127.0.0.1
RBAC_DB_PORT=3306
RBAC_DB_DATABASE=marg-rbac
RBAC_DB_USERNAME=root
RBAC_DB_PASSWORD=

# RBAC Session & Cache
RBAC_SESSION_DRIVER=redis
RBAC_SESSION_CONNECTION=default
RBAC_CACHE_ENABLED=true
RBAC_CACHE_TTL=3600
RBAC_CACHE_CONNECTION=default
```

5. **Setup databases**
```bash
# Create databases manually in MySQL:
# - klueportal (main)
# - klue_portal_business (business)  
# - marg-rbac (RBAC)

# Run complete database setup
php artisan marg:refresh-db --seed
```

6. **Build assets**
```bash
npm run build
```

## 🎯 Database Commands

### Safe Migration Commands

**⚠️ Important:** Direct `php artisan migrate` is **NOT recommended** in this multi-database architecture. Use these specific commands:

#### Complete Setup
```bash
# Refresh all databases and seed with test data
php artisan marg:refresh-db --seed

# Refresh all databases without seeding  
php artisan marg:refresh-db --force
```

#### Database-Specific Commands
```bash
# RBAC database only
php artisan rbac:migrate
php artisan rbac:fresh-migrate --force
php artisan rbac:seed

# Business database only
php artisan migrate --database=business_db --path=database/migrations/business-db

# Original database only  
php artisan migrate --database=mysql --path=database/migrations
```

### Safety Features

- **Environment protection**: Destructive commands only work in `local/testing` environments
- **No dangerous operations**: Uses `migrate:fresh` instead of `db:wipe`
- **Connection isolation**: Each database uses its own connection and migrations

## 👥 User Management

### Test User Account
```
Email: test@example.com
Password: password
Role: Admin on 5 tenants
```

### User Profile Architecture
- **Authentication data**: Stored in RBAC database (`users` table)
- **Profile data**: Stored in main database (`profiles` table) 
- **Cross-database relationship**: User → Profile via foreign key

## 🔐 RBAC System

### Role Hierarchy
```
├── ADMIN (Full system access)
├── MANAGER (Elevated permissions)  
└── USER (Standard permissions)
```

### Tenant Management
- **Multi-tenant isolation**: Users can belong to multiple tenants
- **Active tenant switching**: Session-based tenant context
- **Role assignments**: Per-tenant role assignments via `model_has_roles`

### Permission Caching
- **1-hour cache TTL** for optimal performance
- **Redis-based caching** for scalability
- **Automatic cache invalidation** on role changes

## 📊 Models & Relationships

### Core Models

#### User Model (`App\Models\User`)
```php
// Extends package User model
class User extends RbacUser implements FilamentUser, HasTenants
{
    protected $connection = 'rbac';  // Lives in RBAC database
    
    public function profile(): HasOne
    public function getAssignedTenants()  // Override for correct morph mapping
    public function flows(): MorphToMany
}
```

#### Profile Model (`App\Models\Profile`) 
```php
class Profile extends Model
{
    protected $connection = 'mysql';  // Lives in main database
    protected $fillable = ['user_id', 'avatar', 'timezone'];
}
```

#### Flow Model (`App\Models\Flow`)
```php
class Flow extends Model implements RoleableEntity
{
    protected $connection = 'mysql';  // Lives in main database
    use ManagesParticipants;  // RBAC functionality
}
```

### Cross-Database Relationships

The application handles relationships across three databases:

```php
// User (RBAC) → Profile (Main)
$user->profile->avatar

// User (RBAC) → Flows (Main) via RBAC pivot
$user->flows()->where('status', 'active')

// Flow (Main) → Documents (Business) 
$flow->documents()->where('type', 'requirement')
```

## 🛠️ Development Guidelines

### File Organization
```
database/migrations/
├── /                          # Main database migrations  
├── business-db/              # Business database migrations
└── rbac/                     # RBAC database migrations (published)
```

### Model Conventions
```php
// Database connections via traits
use App\Concerns\Database\LivesInOriginalDB;    // mysql connection
use App\Concerns\Database\LivesInBusinessDB;    // business_db connection  
use Hdaklue\MargRbac\Concerns\Database\LivesInRbacDB;  // rbac connection

// RBAC functionality
use Hdaklue\MargRbac\Contracts\Role\RoleableEntity;
use Hdaklue\MargRbac\Concerns\Role\ManagesParticipants;
```

### Enum Usage
```php
// Application enums (in app/Enums/)
use App\Enums\AssigneeRole;         // ASSIGNEE, APPROVER, REVIEWER, OBSERVER
use App\Enums\FlowStage;            // Flow workflow stages

// Package enums (from marg-rbac package)
use Hdaklue\MargRbac\Enums\Role\RoleEnum;  // ADMIN, MANAGER, USER
```

## 🎨 Frontend Stack

- **Laravel Livewire 3** - Server-side reactivity
- **Alpine.js 3** - Client-side interactivity  
- **Tailwind CSS 4** - Utility-first styling
- **Filament 4** - Admin interface
- **Hero Icons** - SVG icon set

### UI Guidelines
- Use `zinc` instead of `gray`, `sky` instead of `blue`
- Alpine event syntax: `@click`, `.stop`, `.prevent`, `.window`
- Components: `x-tooltip`, `x-anchor` for dropdowns
- Color palette: Sky (primary), Zinc (gray), Indigo (secondary)

## 📦 Package Dependencies

### Core Packages
- **hdaklue/marg-rbac** - Multi-tenant RBAC system
- **lorisleiva/laravel-actions** - Single-purpose action classes
- **filament/filament** - Admin interface framework

### Configuration
- **Package config**: Published to `config/margrbac.php`
- **Environment overrides**: All settings configurable via `.env`
- **No hardcoded values**: Everything uses environment variables

## 🧪 Testing

### Test Structure
```bash
# Run all tests
php artisan test

# Run specific tests  
php artisan test tests/Feature/ExampleTest.php

# Filter by test name
php artisan test --filter=testName
```

### Test User Setup
```php
// Factory usage
$user = User::factory()->create();
$user = User::factory()->admin()->create();  // With admin role

// Tenant assignments
$tenant = Tenant::factory()->create();
$tenant->addParticipant($user, RoleEnum::ADMIN);
```

## 🚀 Deployment

### Environment Requirements
- **Production safety**: Destructive commands disabled in production
- **Database separation**: Ensure all three databases exist
- **Redis configuration**: Required for caching and sessions
- **Queue workers**: Recommended for background processing

### Performance Optimization
- **RBAC caching**: 1-hour TTL with Redis
- **Database indexing**: Proper indexes on foreign keys and polymorphic relationships
- **ULID usage**: Better performance than UUID for primary keys

## 📈 Performance Metrics

- **RBAC response time**: 226ms average
- **Memory usage**: ~5MB for role resolution
- **Cache hit ratio**: >90% for role assignments
- **Database connections**: Optimized connection pooling

## 🔧 Troubleshooting

### Common Issues

1. **Notification errors**: Ensure notifications table exists in RBAC database
2. **Tenant access issues**: Check user has proper tenant assignments  
3. **Migration conflicts**: Use database-specific migration commands
4. **Cache issues**: Clear RBAC cache with `php artisan cache:clear`

### Debug Commands
```bash
# Check database connections
php artisan tinker
> config('database.connections')

# Verify user tenant assignments  
> $user = User::find(1)
> $user->getAssignedTenants()

# Test RBAC functionality
> $user->isAssignedTo($tenant)
```

## 📝 License

This project is proprietary software. All rights reserved.

## 🤝 Contributing

1. Follow existing code conventions
2. Use database-specific migration commands
3. Test across all three databases  
4. Maintain RBAC performance standards
5. Document any architectural changes

---

**Built with ❤️ using Laravel 12, Livewire 3, and the power of multi-tenant RBAC.**