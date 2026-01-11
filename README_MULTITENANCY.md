# Laravel Filament Multi-Tenancy Implementation Guide

## Overview

This guide outlines the complete implementation of a multi-tenancy solution for the Laravel Filament application. This will replace the current subdomain-based approach with a proper multi-tenant architecture that supports:

- Complete data isolation between schools
- Feature-based access control
- Subscription tiers (Basic, Premium, Enterprise)
- Custom configurations per school
- Easy maintenance and updates

## Architecture Overview

### Database Strategy: Shared Database with Tenant Scoping

All schools share the same database but data is isolated using `tenant_id` columns. This provides better performance and easier maintenance compared to separate databases.

### Key Components

1. **Tenant Resolution**: Automatic tenant detection via domain/subdomain
2. **Global Scopes**: Automatic data filtering by tenant
3. **Feature Gates**: Subscription-based feature access
4. **Dynamic UI**: Tenant-specific navigation and resources

---

## Step 1: Database Migration

### Create Tenant Table

```sql
-- Run this migration first
php artisan make:migration create_tenants_table

-- Migration file content:
public function up()
{
    Schema::create('tenants', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('domain')->unique();
        $table->enum('subscription_tier', ['basic', 'premium', 'enterprise'])->default('basic');
        $table->json('features')->nullable(); // Available features for this tenant
        $table->json('custom_config')->nullable(); // School-specific configurations
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('tenants');
}
```

### Add Tenant ID to All Tables

Create a migration to add `tenant_id` to all existing tables:

```sql
php artisan make:migration add_tenant_id_to_all_tables

public function up()
{
    // List of all tables that need tenant_id
    $tables = [
        'users',
        'employee_profiles',
        'salary_histories',
        'departments',
        'units',
        'salary_structures',
        'temporary_deductions',
        'bank_details',
        // Add all other tables here
    ];

    foreach ($tables as $table) {
        Schema::table($table, function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->index('tenant_id');
        });
    }
}

public function down()
{
    $tables = ['users', 'employee_profiles', /* ... all tables */];

    foreach ($tables as $table) {
        Schema::table($table, function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
}
```

### Populate Initial Tenant Data

```sql
php artisan make:migration populate_initial_tenants

public function up()
{
    // Get existing schools from your current system
    // This assumes you have some way to identify schools currently
    $schools = DB::table('your_current_school_table')->get();

    foreach ($schools as $school) {
        DB::table('tenants')->insert([
            'name' => $school->name,
            'domain' => $school->domain, // e.g., 'school1.yourapp.com'
            'subscription_tier' => 'basic', // Set based on current subscriptions
            'features' => json_encode(['employees', 'basic_reports']), // Basic features
            'custom_config' => json_encode([]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
```

---

## Step 2: Core Models and Traits

### Create Tenant Model

```php
// app/Models/Tenant.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'subscription_tier',
        'features',
        'custom_config',
        'is_active'
    ];

    protected $casts = [
        'features' => 'array',
        'custom_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function getCustomField(string $key, $default = null)
    {
        return data_get($this->custom_config, $key, $default);
    }

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class);
    }
}
```

### Create TenantAware Trait

```php
// app/Models/Concerns/TenantAware.php
<?php

namespace App\Models\Concerns;

use App\Scopes\TenantScope;

trait TenantAware
{
    protected static function bootTenantAware()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

### Create TenantScope

```php
// app/Scopes/TenantScope.php
<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $tenantId = session('tenant_id') ?? app('tenant')->id ?? null;

        if ($tenantId) {
            $builder->where('tenant_id', $tenantId);
        }
    }

    public function extend(Builder $builder)
    {
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
```

### Update All Models to Use TenantAware Trait

Add to all your models:

```php
// Example: app/Models/User.php
<?php

namespace App\Models;

use App\Models\Concerns\TenantAware;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use TenantAware; // Add this line

    // ... rest of your User model
}

// Do this for ALL models: EmployeeProfile, SalaryHistory, Department, etc.
```

---

## Step 3: Middleware and Tenant Resolution

### Create Tenant Middleware

```php
// app/Http/Middleware/TenantMiddleware.php
<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        // Remove 'www.' if present
        $host = str_replace('www.', '', $host);

        $tenant = Tenant::where('domain', $host)->first();

        if (!$tenant) {
            abort(404, 'School not found');
        }

        if (!$tenant->is_active) {
            abort(403, 'School account is inactive');
        }

        // Store tenant information
        session(['tenant_id' => $tenant->id]);
        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
```

### Register Middleware

```php
// app/Http/Kernel.php or bootstrap/app.php (Laravel 11)
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \App\Http\Middleware\TenantMiddleware::class,
    ],
];
```

---

## Step 4: Feature-Based Access Control

### Update AppServiceProvider

```php
// app/Providers/AppServiceProvider.php
<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Feature-based gates
        Gate::before(function ($user, $ability) {
            $tenant = app('tenant');

            if (!$tenant) {
                return false;
            }

            // Check feature permissions
            if (str_starts_with($ability, 'feature:')) {
                $feature = str_replace('feature:', '', $ability);
                return $tenant->hasFeature($feature);
            }

            // Check subscription tier permissions
            if (str_starts_with($ability, 'tier:')) {
                $requiredTier = str_replace('tier:', '', $ability);
                $tierHierarchy = ['basic' => 1, 'premium' => 2, 'enterprise' => 3];
                $currentTier = $tierHierarchy[$tenant->subscription_tier] ?? 0;
                $requiredTierLevel = $tierHierarchy[$requiredTier] ?? 0;

                return $currentTier >= $requiredTierLevel;
            }

            return null; // Continue with other gate checks
        });
    }
}
```

---

## Step 5: Filament Configuration

### Update AdminPanelProvider

```php
// app/Providers/Filament/AdminPanelProvider.php
<?php

namespace App\Providers\Filament;

use App\Models\Tenant;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('School Management System')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->tenant(Tenant::class, slugAttribute: 'domain')
            ->tenantMiddleware([
                \App\Http\Middleware\TenantMiddleware::class,
            ])
            ->resources($this->getTenantResources())
            ->pages($this->getTenantPages())
            ->navigationGroups($this->getTenantNavigation());
    }

    private function getTenantResources(): array
    {
        $tenant = app('tenant');
        $resources = [
            // Core resources available to all tenants
            \App\Filament\Resources\EmployeeResource::class,
            \App\Filament\Resources\UserResource::class,
        ];

        // Add feature-specific resources
        if ($tenant->hasFeature('salary_structures')) {
            $resources[] = \App\Filament\Resources\SalaryStructureResource::class;
        }

        if ($tenant->hasFeature('advanced_reports')) {
            $resources[] = \App\Filament\Resources\AdvancedReportResource::class;
        }

        if ($tenant->subscription_tier === 'enterprise') {
            $resources[] = \App\Filament\Resources\ApiKeyResource::class;
            $resources[] = \App\Filament\Resources\CustomFieldResource::class;
        }

        return $resources;
    }

    private function getTenantPages(): array
    {
        $tenant = app('tenant');
        $pages = [
            \App\Filament\Pages\Dashboard::class,
        ];

        if ($tenant->hasFeature('payroll_reports')) {
            $pages[] = \App\Filament\Pages\PayrollReports::class;
        }

        return $pages;
    }

    private function getTenantNavigation(): array
    {
        $tenant = app('tenant');
        $navigation = [
            'Employee Management',
            'User Management',
        ];

        if ($tenant->hasFeature('payroll')) {
            $navigation[] = 'Payroll Management';
        }

        if ($tenant->hasFeature('reports')) {
            $navigation[] = 'Reports & Analytics';
        }

        return $navigation;
    }
}
```

### Update Filament Resources with Feature Checks

```php
// Example: app/Filament/Resources/SalaryStructureResource.php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryStructureResource\Pages;
use App\Models\SalaryStructure;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\Gate;

class SalaryStructureResource extends Resource
{
    protected static ?string $model = SalaryStructure::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Payroll Management';

    public static function canViewAny(): bool
    {
        return Gate::allows('feature:salary_structures');
    }

    public static function canCreate(): bool
    {
        return Gate::allows('feature:salary_structures');
    }

    // ... rest of your resource definition
}
```

---

## Step 6: Data Migration Scripts

### Create Data Migration Command

```php
// app/Console/Commands/MigrateTenantData.php
<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateTenantData extends Command
{
    protected $signature = 'tenants:migrate-data {--school= : Specific school domain to migrate}';

    protected $description = 'Migrate existing data to tenant structure';

    public function handle()
    {
        $specificSchool = $this->option('school');

        if ($specificSchool) {
            $tenant = Tenant::where('domain', $specificSchool)->first();
            if (!$tenant) {
                $this->error("Tenant not found for domain: $specificSchool");
                return 1;
            }
            $this->migrateTenantData($tenant);
        } else {
            $tenants = Tenant::all();
            foreach ($tenants as $tenant) {
                $this->info("Migrating data for: {$tenant->name}");
                $this->migrateTenantData($tenant);
            }
        }

        $this->info('Data migration completed!');
        return 0;
    }

    private function migrateTenantData(Tenant $tenant)
    {
        // Example migration logic - adjust based on your current data structure
        // This assumes you have some identifier to match schools

        // Update users for this tenant
        DB::table('users')
            ->where('school_identifier', $tenant->domain) // Adjust this condition
            ->update(['tenant_id' => $tenant->id]);

        // Update employees
        DB::table('employee_profiles')
            ->where('school_domain', $tenant->domain) // Adjust this condition
            ->update(['tenant_id' => $tenant->id]);

        // Update other tables...
        // Add similar updates for all tables
    }
}
```

Run the migration:

```bash
php artisan tenants:migrate-data
# Or for specific school:
php artisan tenants:migrate-data --school=school1.yourapp.com
```

---

## Step 7: Testing Setup

### Create Multi-Tenant Test Trait

```php
// tests/Concerns/MultiTenantTest.php
<?php

namespace Tests\Concerns;

use App\Models\Tenant;
use App\Models\User;

trait MultiTenantTest
{
    protected Tenant $tenant;

    protected function setUpTenant(): void
    {
        $this->tenant = Tenant::factory()->create([
            'domain' => 'test-' . uniqid() . '.example.com',
            'subscription_tier' => 'premium',
            'features' => ['employees', 'salary_structures', 'reports'],
        ]);

        // Set tenant context
        app()->instance('tenant', $this->tenant);
        session(['tenant_id' => $this->tenant->id]);
    }

    protected function createTenantUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
        ], $attributes));
    }
}
```

### Example Test

```php
// tests/Feature/MultiTenantTest.php
<?php

namespace Tests\Feature;

use App\Models\EmployeeProfile;
use App\Models\Tenant;
use App\Models\User;
use Tests\Concerns\MultiTenantTest;
use Tests\TestCase;

class MultiTenantTest extends TestCase
{
    use MultiTenantTest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    public function test_tenant_data_isolation()
    {
        // Create data for tenant 1
        $user1 = $this->createTenantUser();
        $employee1 = EmployeeProfile::factory()->create([
            'tenant_id' => $this->tenant->id,
            'full_name' => 'John Doe',
        ]);

        // Create tenant 2
        $tenant2 = Tenant::factory()->create([
            'domain' => 'tenant2.example.com',
        ]);

        $user2 = User::factory()->create([
            'tenant_id' => $tenant2->id,
        ]);

        $employee2 = EmployeeProfile::factory()->create([
            'tenant_id' => $tenant2->id,
            'full_name' => 'Jane Smith',
        ]);

        // Test isolation
        $this->actingAs($user1);

        $response = $this->get('/admin/employees');
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');

        // Switch to tenant 2
        app()->instance('tenant', $tenant2);
        session(['tenant_id' => $tenant2->id]);
        $this->actingAs($user2);

        $response = $this->get('/admin/employees');
        $response->assertSee('Jane Smith');
        $response->assertDontSee('John Doe');
    }
}
```

---

## Step 8: Performance Optimization

### Database Indexes

```sql
-- Add these indexes for better performance
CREATE INDEX idx_tenant_created_at ON users (tenant_id, created_at);
CREATE INDEX idx_tenant_status ON employee_profiles (tenant_id, status);
CREATE INDEX idx_tenant_month_year ON salary_histories (tenant_id, salary_month, salary_year);

-- For large tables, consider partitioning
-- ALTER TABLE salary_histories PARTITION BY HASH(tenant_id) PARTITIONS 10;
```

### Caching Strategy

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    // Tenant-specific cache prefix
    config(['cache.prefix' => 'tenant_' . (app('tenant')->id ?? 'global')]);
}
```

---

## Step 9: Deployment Checklist

- [ ] Run database migrations
- [ ] Update all models with TenantAware trait
- [ ] Configure tenant domains in DNS
- [ ] Update web server configuration for subdomains
- [ ] Test tenant isolation
- [ ] Test feature gating
- [ ] Configure SSL certificates for all domains
- [ ] Set up monitoring and logging per tenant
- [ ] Create backup strategy for multi-tenant data

---

## Subscription Tiers Configuration

### Feature Mapping

```php
// config/subscription-tiers.php
return [
    'basic' => [
        'name' => 'Basic',
        'price' => 29.99,
        'features' => [
            'employees',
            'basic_reports',
            'user_management',
        ],
    ],
    'premium' => [
        'name' => 'Premium',
        'price' => 79.99,
        'features' => [
            'employees',
            'basic_reports',
            'user_management',
            'salary_structures',
            'advanced_reports',
            'payroll_management',
        ],
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'price' => 199.99,
        'features' => [
            'employees',
            'basic_reports',
            'user_management',
            'salary_structures',
            'advanced_reports',
            'payroll_management',
            'custom_fields',
            'api_access',
            'advanced_analytics',
            'multi_location_support',
        ],
    ],
];
```

---

## Troubleshooting

### Common Issues

1. **Tenant Not Found**: Check domain configuration and DNS settings
2. **Data Not Isolated**: Ensure all models use TenantAware trait
3. **Features Not Working**: Verify tenant features array and gate definitions
4. **Performance Issues**: Check database indexes and consider partitioning

### Debug Commands

```bash
# Check current tenant
php artisan tinker
>>> app('tenant')

# List all tenants
php artisan tinker
>>> App\Models\Tenant::all()

# Check user tenant assignment
php artisan tinker
>>> Auth::user()->tenant
```

---

## Maintenance Tasks

### Regular Tasks

1. **Monitor Tenant Usage**: Track database growth per tenant
2. **Update Subscription Tiers**: Modify features based on business needs
3. **Backup Strategy**: Ensure tenant-specific backups
4. **Performance Monitoring**: Watch for slow queries per tenant

### Scaling Considerations

- **Database**: Consider read replicas for reporting-heavy tenants
- **File Storage**: Use tenant-specific directories or cloud storage prefixes
- **Caching**: Implement tenant-aware caching strategies
- **Queue Jobs**: Tag jobs by tenant for better monitoring

---

## Security Considerations

1. **Data Encryption**: Encrypt sensitive tenant data
2. **Access Control**: Implement proper role-based permissions
3. **Audit Logging**: Log all tenant-specific actions
4. **Rate Limiting**: Implement per-tenant rate limits
5. **Backup Security**: Secure tenant data backups

This implementation provides a robust, scalable multi-tenancy solution that eliminates the need for code duplication across school instances while providing feature-based access control and subscription management.