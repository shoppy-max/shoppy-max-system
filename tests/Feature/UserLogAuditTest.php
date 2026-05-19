<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserLogAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_failure_success_and_logout_are_audited(): void
    {
        $user = User::factory()->create([
            'email' => 'audit-admin@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('user_logs', [
            'action' => 'login_failed',
            'module' => 'Auth',
            'user_email' => $user->email,
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('user_logs', [
            'action' => 'login_succeeded',
            'module' => 'Auth',
            'user_id' => $user->id,
        ]);

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertDatabaseHas('user_logs', [
            'action' => 'logout',
            'module' => 'Auth',
            'user_id' => $user->id,
        ]);
    }

    public function test_crud_requests_and_model_changes_are_audited_with_sanitized_payloads(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('units.store'), [
            '_token' => 'secret-csrf-token',
            'name' => 'Audit Unit',
            'short_name' => 'AU',
            'password' => 'must-not-be-stored',
        ])->assertRedirect(route('units.index'));

        $unit = Unit::where('name', 'Audit Unit')->firstOrFail();

        $this->assertDatabaseHas('user_logs', [
            'action' => 'created',
            'module' => 'Product Metadata',
            'auditable_type' => Unit::class,
            'auditable_id' => $unit->id,
            'user_id' => $user->id,
        ]);

        $requestLog = UserLog::where('route_name', 'units.store')
            ->where('method', 'POST')
            ->where('action', 'submitted')
            ->latest('occurred_at')
            ->firstOrFail();

        $this->assertSame('[redacted]', data_get($requestLog->request_data, '_token'));
        $this->assertSame('[redacted]', data_get($requestLog->request_data, 'password'));
        $this->assertSame('Audit Unit', data_get($requestLog->request_data, 'name'));

        $this->actingAs($user)->put(route('units.update', $unit), [
            'name' => 'Audit Unit Updated',
            'short_name' => 'AU2',
        ])->assertRedirect(route('units.index'));

        $updateLog = UserLog::where('action', 'updated')
            ->where('auditable_type', Unit::class)
            ->where('auditable_id', $unit->id)
            ->latest('occurred_at')
            ->firstOrFail();

        $this->assertSame('Audit Unit', data_get($updateLog->old_values, 'name'));
        $this->assertSame('Audit Unit Updated', data_get($updateLog->new_values, 'name'));

        $this->actingAs($user)->delete(route('units.destroy', $unit))->assertRedirect(route('units.index'));

        $this->assertDatabaseHas('user_logs', [
            'action' => 'deleted',
            'auditable_type' => Unit::class,
            'auditable_id' => $unit->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_upload_requests_log_file_metadata_without_file_contents(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('products.store'), [
            'image' => UploadedFile::fake()->image('proof.jpg', 16, 16),
        ])->assertSessionHasErrors();

        $requestLog = UserLog::where('route_name', 'products.store')
            ->where('method', 'POST')
            ->latest('occurred_at')
            ->firstOrFail();

        $this->assertSame('proof.jpg', data_get($requestLog->request_data, 'files.image.name'));
        $this->assertSame('image/jpeg', data_get($requestLog->request_data, 'files.image.mime_type'));
        $this->assertArrayNotHasKey('contents', data_get($requestLog->request_data, 'files.image', []));
    }

    public function test_authenticated_page_views_are_audited_without_self_logging_user_logs_page(): void
    {
        $user = User::factory()->create();
        $this->grantViewUserLogs($user);

        $this->actingAs($user)->get(route('units.index'))->assertOk();

        $this->assertDatabaseHas('user_logs', [
            'action' => 'viewed',
            'module' => 'Product Metadata',
            'route_name' => 'units.index',
            'method' => 'GET',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->get(route('user-logs.index'))->assertOk();

        $this->assertDatabaseMissing('user_logs', [
            'route_name' => 'user-logs.index',
            'action' => 'viewed',
        ]);
    }

    public function test_query_string_exports_are_audited_as_downloads(): void
    {
        Excel::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('reports.stock', ['export' => 'excel']));

        $this->assertDatabaseHas('user_logs', [
            'action' => 'downloaded',
            'module' => 'Reports',
            'route_name' => 'reports.stock',
            'method' => 'GET',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_logs_can_export_full_filtered_excel_output(): void
    {
        Excel::fake();
        Carbon::setTestNow('2026-05-20 12:00:00');
        $user = User::factory()->create();
        $this->grantViewUserLogs($user);

        try {
            UserLog::create([
                'action' => 'downloaded',
                'module' => 'Orders',
                'description' => 'Downloaded order export',
                'route_name' => 'orders.export',
                'method' => 'GET',
                'occurred_at' => now(),
            ]);
            UserLog::create([
                'action' => 'viewed',
                'module' => 'Reports',
                'description' => 'Viewed reports',
                'route_name' => 'reports.index',
                'method' => 'GET',
                'occurred_at' => now(),
            ]);

            $this->actingAs($user)->get(route('user-logs.export', [
                'format' => 'excel',
                'module' => 'Orders',
            ]));

            Excel::assertDownloaded('user_logs_20260520_120000.xlsx');

            $this->assertDatabaseHas('user_logs', [
                'action' => 'downloaded',
                'module' => 'Audit',
                'route_name' => 'user-logs.export',
                'method' => 'GET',
                'user_id' => $user->id,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_user_logs_page_filters_and_shows_clear_details(): void
    {
        $user = User::factory()->create(['name' => 'Audit Viewer']);
        $this->grantViewUserLogs($user);
        UserLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'action' => 'updated',
            'module' => 'Product Metadata',
            'description' => 'Updated Unit: Filter Needle',
            'auditable_type' => Unit::class,
            'auditable_id' => 55,
            'auditable_label' => 'Filter Needle',
            'route_name' => 'units.update',
            'url' => 'http://127.0.0.1/admin/units/55',
            'method' => 'PUT',
            'status_code' => 302,
            'request_data' => ['name' => 'Filter Needle'],
            'old_values' => ['name' => 'Old Needle'],
            'new_values' => ['name' => 'Filter Needle'],
            'metadata' => ['source' => 'feature-test'],
            'occurred_at' => now(),
        ]);
        UserLog::create([
            'action' => 'downloaded',
            'module' => 'Reports',
            'description' => 'Downloaded hidden report',
            'occurred_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->get(route('user-logs.index', [
            'search' => 'Filter Needle',
            'module' => 'Product Metadata',
        ]));

        $response->assertOk();
        $response->assertSee('Filter Needle');
        $response->assertSee('Audit Viewer');
        $response->assertSee('Updated Unit');
        $response->assertSee('Units - Update');
        $response->assertSee('Old Needle');
        $response->assertSee('feature-test');
        $response->assertDontSee('Coming Soon');
        $response->assertDontSee('Downloaded hidden report');
    }

    public function test_user_logs_access_requires_permission(): void
    {
        $user = User::factory()->create();
        $auditor = User::factory()->create();
        $this->grantViewUserLogs($auditor);

        $this->actingAs($user)->get(route('user-logs.index'))->assertForbidden();
        $this->actingAs($auditor)->get(route('user-logs.index'))->assertOk();
    }

    public function test_post_download_routes_are_downloaded_and_waybill_lists_are_viewed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('orders.bulk-pdf'), [
            'order_ids' => [],
        ])->assertRedirect();

        $this->assertDatabaseHas('user_logs', [
            'action' => 'downloaded',
            'module' => 'Orders',
            'route_name' => 'orders.bulk-pdf',
            'method' => 'POST',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->get(route('orders.waybill.index'))->assertOk();

        $this->assertDatabaseHas('user_logs', [
            'action' => 'viewed',
            'module' => 'Orders',
            'route_name' => 'orders.waybill.index',
            'method' => 'GET',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('user_logs', [
            'action' => 'downloaded',
            'route_name' => 'orders.waybill.index',
            'method' => 'GET',
        ]);
    }

    public function test_route_parameters_and_unauthenticated_api_attempts_are_audited(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();

        $this->assertDatabaseHas('user_logs', [
            'action' => 'viewed',
            'status_code' => 401,
            'method' => 'GET',
        ]);

        $user = User::factory()->create();
        $unit = Unit::create(['name' => 'Route Param Unit', 'short_name' => 'RPU']);

        $this->actingAs($user)->get(route('units.edit', $unit))->assertOk();

        $requestLog = UserLog::where('route_name', 'units.edit')
            ->where('action', 'viewed')
            ->latest('occurred_at')
            ->firstOrFail();

        $this->assertSame(Unit::class, data_get($requestLog->request_data, 'route_parameters.unit.type'));
        $this->assertSame($unit->id, data_get($requestLog->request_data, 'route_parameters.unit.id'));
    }

    public function test_user_logs_show_admin_readable_labels_instead_of_code_first(): void
    {
        $user = User::factory()->create();
        $this->grantViewUserLogs($user);

        UserLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'action' => 'viewed',
            'module' => 'Product Metadata',
            'description' => 'Viewed Unit edit page',
            'auditable_type' => Unit::class,
            'auditable_id' => 15,
            'route_name' => 'units.edit',
            'method' => 'GET',
            'url' => 'http://127.0.0.1/admin/units/15/edit',
            'request_data' => [
                'route_parameters' => [
                    'unit' => [
                        'type' => Unit::class,
                        'id' => 15,
                        'label' => 'Bottle',
                    ],
                ],
            ],
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('user-logs.index', [
            'search' => 'Opened Units Edit Page',
        ]));

        $response->assertOk();
        $response->assertSee('Opened Units Edit Page');
        $response->assertSee('Viewed · Product Metadata');
        $response->assertSee('Units - Edit Page');
        $response->assertSee('Unit #15');
        $response->assertSee('Clear Activity');
        $response->assertSee('Technical Route');
        $response->assertSee('units.edit');
        $response->assertSee('Bottle');
        $response->assertDontSee('App\\\\Models\\\\Unit');
    }

    public function test_generated_route_names_and_generic_request_subjects_are_hidden_from_admin_labels(): void
    {
        $user = User::factory()->create();
        $this->grantViewUserLogs($user);

        UserLog::create([
            'action' => 'viewed',
            'module' => 'System',
            'description' => 'Viewed request',
            'route_name' => 'generated::JEbOkGiYcffJWTAk',
            'method' => 'GET',
            'url' => 'http://127.0.0.1/api/user',
            'status_code' => 401,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('user-logs.index', [
            'search' => 'Api User',
        ]));

        $response->assertOk();
        $response->assertSee('Viewed Api User');
        $response->assertSee('Page View');
        $response->assertSee('Technical Route');
        $response->assertSee('generated::JEbOkGiYcffJWTAk');
        $response->assertDontSee('Viewed Generated::');
        $response->assertDontSee('Viewed request');
    }

    public function test_generated_login_request_is_shown_as_clear_auth_activity(): void
    {
        $user = User::factory()->create(['name' => 'Super Admin']);
        $this->grantViewUserLogs($user);

        UserLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'action' => 'submitted',
            'module' => 'System',
            'description' => 'Submitted request',
            'route_name' => 'generated::BCIjx5PT7YAMhcPn',
            'method' => 'POST',
            'url' => 'http://127.0.0.1:8000/login',
            'status_code' => 302,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('user-logs.index', [
            'search' => 'Submitted Login Form',
        ]));

        $response->assertOk();
        $response->assertSee('Super Admin');
        $response->assertSee('Submitted Login Form');
        $response->assertSee('Submitted · System');
        $response->assertSee('Authentication');
        $response->assertSee('Login');
        $response->assertSee('Technical Route');
        $response->assertSee('generated::BCIjx5PT7YAMhcPn');
        $response->assertDontSee('Submitted Generated::');
        $response->assertDontSee('Submitted request');
    }

    private function grantViewUserLogs(User $user): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'view user logs',
            'guard_name' => 'web',
        ]);

        $user->givePermissionTo($permission);
    }
}
