<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RbacPermissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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

    public function test_role_and_user_forms_show_grouped_permission_assignment(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::where('email', 'admin@shoppy-max.com')->firstOrFail();
        $role = \Spatie\Permission\Models\Role::where('name', 'admin')->firstOrFail();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.roles.edit', $role))
            ->assertOk()
            ->assertSee('Product Management')
            ->assertSee('Order Management')
            ->assertSee('Manage permissions')
            ->assertSee('data-permission-group', false);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Direct User Permissions')
            ->assertSee('Product Management')
            ->assertSee('Order Management')
            ->assertSee('name="permissions[]"', false);
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
