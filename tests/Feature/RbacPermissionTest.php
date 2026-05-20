<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RbacPermissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_operational_authenticated_route_has_a_rbac_permission_mapping(): void
    {
        $missing = collect(Route::getRoutes())
            ->filter(fn ($route) => in_array('web', $route->gatherMiddleware(), true))
            ->filter(fn ($route) => collect($route->gatherMiddleware())->contains(function ($middleware) {
                return $middleware === 'auth'
                    || str_starts_with((string) $middleware, 'auth:')
                    || str_contains((string) $middleware, 'Authenticate');
            }))
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->reject(fn (string $name) => RbacPermissions::isSystemRoute($name))
            ->reject(fn (string $name) => RbacPermissions::permissionForRoute($name) !== null)
            ->values()
            ->all();

        $this->assertSame([], $missing);
    }

    public function test_authenticated_user_without_permission_cannot_access_operational_routes_directly(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('products.index'))->assertForbidden();
        $this->actingAs($user)->get(route('orders.index'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_super_admin_role_receives_every_defined_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::where('email', 'admin@shoppy-max.com')->firstOrFail();

        $this->assertTrue($admin->hasRole('super admin'));
        $expected = RbacPermissions::allPermissionNames();
        $actual = $admin->getAllPermissions()->pluck('name')->all();
        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);

        $this->actingAs($admin)->get(route('products.index'))->assertOk();
        $this->actingAs($admin)->get(route('orders.index'))->assertOk();
        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
    }

    public function test_legacy_admin_role_is_migrated_to_manager_role(): void
    {
        $legacyRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $legacyUser = User::factory()->create();
        $legacyUser->assignRole($legacyRole);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertDatabaseMissing('roles', ['name' => 'admin', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'manager', 'guard_name' => 'web']);
        $this->assertTrue($legacyUser->fresh()->hasRole('manager'));

        $managerRole = Role::where('name', 'manager')->firstOrFail();
        $this->assertTrue($managerRole->hasPermissionTo('view dashboard'));
        $this->assertTrue($managerRole->hasPermissionTo('view users'));
        $this->assertFalse($managerRole->hasPermissionTo('delete users'));
    }

    public function test_role_and_user_forms_show_grouped_permission_assignment(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::where('email', 'admin@shoppy-max.com')->firstOrFail();
        $role = Role::where('name', 'manager')->firstOrFail();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.roles.edit', $role))
            ->assertOk()
            ->assertSee('Product Management')
            ->assertSee('Order Management')
            ->assertSee('Assign role and user permissions')
            ->assertSee('data-permission-group', false);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Direct User Permissions')
            ->assertSee('Product Management')
            ->assertSee('Order Management')
            ->assertSee('name="permissions[]"', false);
    }

    public function test_every_catalog_permission_is_visible_and_assignable_on_role_and_user_forms(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::where('email', 'admin@shoppy-max.com')->firstOrFail();
        $catalogPermissionNames = RbacPermissions::allPermissionNames();

        $roleCreate = $this->actingAs($admin)
            ->get(route('admin.roles.create'))
            ->assertOk()
            ->assertSee('Search permission, group, or action...');

        $userCreate = $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Search permission, group, or action...');

        foreach ($catalogPermissionNames as $permissionName) {
            $roleCreate->assertSee('value="'.$permissionName.'"', false);
            $userCreate->assertSee('value="'.$permissionName.'"', false);
        }
    }

    public function test_permission_management_page_exposes_seeded_catalog_as_searchable_system_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::where('email', 'admin@shoppy-max.com')->firstOrFail();
        $catalogPermissionNames = RbacPermissions::allPermissionNames();

        $this->assertSame(
            count($catalogPermissionNames),
            Permission::whereIn('name', $catalogPermissionNames)->count()
        );

        foreach (['assign permissions', 'view orders', 'place stock in stores', 'export reports'] as $permissionName) {
            $this->actingAs($admin)
                ->get(route('admin.permissions.index', ['search' => $permissionName]))
                ->assertOk()
                ->assertSee($permissionName)
                ->assertSee('System')
                ->assertSee('Permissions are controlled by the RBAC catalog')
                ->assertDontSee('Create Permission');
        }

        $this->assertFalse(Route::has('admin.permissions.create'));
        $this->assertFalse(Route::has('admin.permissions.store'));
        $this->assertFalse(Route::has('admin.permissions.edit'));
        $this->assertFalse(Route::has('admin.permissions.destroy'));
        $this->actingAs($admin)->get('/admin/permissions/create')->assertNotFound();
    }

    public function test_grouped_permission_catalog_is_unique_and_keeps_quick_create_routes_on_create_permissions(): void
    {
        $groupedNames = collect(RbacPermissions::groupedForDisplay())
            ->flatMap(fn (array $group) => collect($group['permissions'])->pluck('name'));

        $this->assertSame(
            $groupedNames->unique()->values()->all(),
            $groupedNames->values()->all()
        );

        $this->assertSame('create categories', RbacPermissions::permissionForRoute('quick.category.store'));
        $this->assertSame('create sub categories', RbacPermissions::permissionForRoute('quick.subcategory.store'));
        $this->assertSame('create units', RbacPermissions::permissionForRoute('quick.unit.store'));
    }

    public function test_role_assignment_through_admin_form_unlocks_only_selected_routes(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::where('email', 'admin@shoppy-max.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), [
                'name' => 'order viewer',
                'permissions' => ['view orders'],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', 'order viewer')->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('view orders'));

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get(route('orders.index'))->assertOk();
        $this->actingAs($user)->get(route('products.index'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
    }

    public function test_direct_user_permission_assignment_through_admin_form_unlocks_only_selected_route(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::where('email', 'admin@shoppy-max.com')->firstOrFail();
        $user = User::factory()->create([
            'name' => 'Report Clerk',
            'email' => 'report-clerk@example.test',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'permissions' => ['view reports'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertTrue($user->can('view reports'));

        $this->actingAs($user)->get(route('reports.index'))->assertOk();
        $this->actingAs($user)->get(route('products.index'))->assertForbidden();
        $this->actingAs($user)->get(route('orders.index'))->assertForbidden();
    }

    public function test_role_permission_assignment_requires_explicit_assign_permission_without_partial_mutation(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $creator = User::factory()->create();
        $creator->givePermissionTo('create roles');

        $this->actingAs($creator)
            ->post(route('admin.roles.store'), [
                'name' => 'shipping clerk',
                'permissions' => ['view orders'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'shipping clerk']);

        $editor = User::factory()->create();
        $editor->givePermissionTo('edit roles');
        $role = Role::create(['name' => 'packing clerk', 'guard_name' => 'web']);

        $this->actingAs($editor)
            ->put(route('admin.roles.update', $role), [
                'name' => 'packing lead',
                'permissions' => ['view packing'],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'packing clerk']);
        $this->assertDatabaseMissing('roles', ['id' => $role->id, 'name' => 'packing lead']);
    }
}
