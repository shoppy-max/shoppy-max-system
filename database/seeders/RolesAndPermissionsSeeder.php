<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Support\RbacPermissions;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (RbacPermissions::allPermissionNames() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Create roles
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super admin',
            'guard_name' => 'web',
        ]);
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);
        $resellerRole = Role::firstOrCreate([
            'name' => 'reseller',
            'guard_name' => 'web',
        ]);
        $directResellerRole = Role::firstOrCreate([
            'name' => 'direct reseller',
            'guard_name' => 'web',
        ]);

        $superAdminRole->syncPermissions(RbacPermissions::allPermissionNames());

        // Assign some permissions to admin
        $adminRole->syncPermissions([
            'view dashboard',
            'view users',
            'create users',
            'edit users',
            'view roles',
            'view permissions',
            'view user logs',
        ]);

        // Keep login redirects valid while operational modules remain RBAC-gated.
        $userRole->syncPermissions(['view dashboard']);
        $resellerRole->syncPermissions(['view dashboard']);
        $directResellerRole->syncPermissions(['view dashboard']);

        // Create super admin user
        // NOTE: For security, change this password immediately after first login in production
        // You can use: php artisan tinker
        // Then: User::where('email', 'admin@shoppy-max.com')->first()->update(['password' => Hash::make('your-secure-password')])
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@shoppy-max.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password', // 'hashed' cast in User model handles hashing
                'email_verified_at' => now(), // Auto-verify email for seeded admin
            ]
        );

        // Do not reset the password on repeated deploys. If the admin user already
        // exists, keep the current password and only ensure the account is verified.
        if (!$superAdmin->wasRecentlyCreated) {
            if (!$superAdmin->email_verified_at) {
                $superAdmin->email_verified_at = now();
                $superAdmin->save();
            }
        }

        $superAdmin->syncRoles(['super admin']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Display warning in console
        $this->command->warn('⚠️  WARNING: Default super admin created with password "password"');
        $this->command->warn('   Please change this password immediately in production!');
        $this->command->info('   Login at: /login with email: admin@shoppy-max.com');
    }
}
