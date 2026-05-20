<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RbacPermissions
{
    /**
     * Routes that are auth/session/self-service plumbing, not operational RBAC surfaces.
     */
    public static function isSystemRoute(string $routeName): bool
    {
        return str_starts_with($routeName, 'generated::')
            || str_starts_with($routeName, 'password.')
            || str_starts_with($routeName, 'verification.')
            || str_starts_with($routeName, 'profile.')
            || in_array($routeName, [
                'login',
                'logout',
                'register',
                'guest.products',
                'sanctum.csrf-cookie',
                'storage.local',
                'storage.local.upload',
            ], true);
    }

    public static function permissionForRequest(Request $request): ?string
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return null;
        }

        if ($request->has('export')) {
            return match ($routeName) {
                'customers.index' => 'export customers',
                'suppliers.index' => 'export suppliers',
                'cities.index' => 'export cities',
                'resellers.index' => 'export direct resellers',
                'direct-resellers.index' => 'export resellers',
                'reports.stock',
                'reports.stock.show',
                'reports.packet-count',
                'reports.product-sales',
                'reports.user-sales' => 'export reports',
                default => self::permissionForRoute($routeName),
            };
        }

        return self::permissionForRoute($routeName);
    }

    public static function permissionForRoute(string $routeName): ?string
    {
        return self::routePermissions()[$routeName] ?? null;
    }

    public static function allPermissionNames(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group) => collect($group['permissions'])->pluck('name'))
            ->unique()
            ->values()
            ->all();
    }

    public static function systemPermissionNames(): array
    {
        return [
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view permissions',
            'assign permissions',
            'view user logs',
            'export user logs',
        ];
    }

    public static function routePermissions(): array
    {
        return collect(self::groups())
            ->flatMap(function (array $group) {
                return collect($group['permissions'])
                    ->flatMap(fn (array $permission) => collect($permission['routes'] ?? [])
                        ->mapWithKeys(fn (string $route) => [$route => $permission['name']]));
            })
            ->all();
    }

    public static function groups(): array
    {
        return [
            [
                'key' => 'workspace',
                'label' => 'Workspace',
                'permissions' => [
                    self::permission('view dashboard', 'View dashboard', ['dashboard']),
                ],
            ],
            [
                'key' => 'admin',
                'label' => 'Admin & RBAC',
                'permissions' => [
                    self::permission('view users', 'View users', ['admin.users.index', 'admin.users.show']),
                    self::permission('create users', 'Create users', ['admin.users.create', 'admin.users.store']),
                    self::permission('edit users', 'Edit users', ['admin.users.edit', 'admin.users.update']),
                    self::permission('delete users', 'Delete users', ['admin.users.destroy']),
                    self::permission('view roles', 'View roles', ['admin.roles.index', 'admin.roles.show']),
                    self::permission('create roles', 'Create roles', ['admin.roles.create', 'admin.roles.store']),
                    self::permission('edit roles', 'Edit roles', ['admin.roles.edit', 'admin.roles.update']),
                    self::permission('assign permissions', 'Assign role and user permissions'),
                    self::permission('delete roles', 'Delete roles', ['admin.roles.destroy']),
                    self::permission('view permissions', 'View permission catalog', ['admin.permissions.index']),
                    self::permission('view user logs', 'View user logs', ['user-logs.index']),
                    self::permission('export user logs', 'Export user logs', ['user-logs.export']),
                ],
            ],
            [
                'key' => 'contacts',
                'label' => 'Contacts',
                'permissions' => [
                    ...self::resourcePermissions('customers', 'customers', includeShow: true),
                    self::permission('export customers', 'Export customers'),
                    ...self::resourcePermissions('suppliers', 'suppliers', includeShow: true),
                    self::permission('export suppliers', 'Export suppliers'),
                    ...self::resourcePermissions('cities', 'cities', includeShow: true),
                    self::permission('export cities', 'Export cities'),
                ],
            ],
            [
                'key' => 'resellers',
                'label' => 'Resellers',
                'permissions' => [
                    ...self::resourcePermissions('direct resellers', 'resellers', includeShow: true),
                    self::permission('export direct resellers', 'Export direct resellers'),
                    self::permission('reset direct reseller passwords', 'Reset direct reseller login passwords', ['resellers.reset-password']),
                    ...self::resourcePermissions('resellers', 'direct-resellers', includeShow: true),
                    self::permission('export resellers', 'Export resellers'),
                    self::permission('reset reseller passwords', 'Reset reseller login passwords', ['direct-resellers.reset-password']),
                    ...self::resourcePermissions('reseller targets', 'reseller-targets', includeShow: true),
                    self::permission('view direct reseller dues', 'View direct reseller dues', ['reseller-dues.index', 'reseller-dues.show']),
                    self::permission('view reseller dues', 'View reseller dues', ['direct-reseller-dues.index', 'direct-reseller-dues.show']),
                ],
            ],
            [
                'key' => 'reseller-payments',
                'label' => 'Reseller Payments',
                'permissions' => [
                    ...self::paymentPermissions('direct reseller payments', 'reseller-payments', includeShow: true),
                    self::permission('import direct reseller payments', 'Import direct reseller payments', [
                        'reseller-payments.import.show',
                        'reseller-payments.import.preview',
                        'reseller-payments.import.store',
                        'reseller-payments.import.template',
                    ]),
                    self::permission('download direct reseller payments', 'Download direct reseller payment vouchers', [
                        'reseller-payments.download',
                        'reseller-payments.download-bulk',
                    ]),
                    self::permission('cancel direct reseller payments', 'Cancel direct reseller payments', ['reseller-payments.cancel']),
                    ...self::paymentPermissions('reseller payments', 'direct-reseller-payments', includeShow: true),
                    self::permission('import reseller payments', 'Import reseller payments', [
                        'direct-reseller-payments.import.show',
                        'direct-reseller-payments.import.preview',
                        'direct-reseller-payments.import.store',
                        'direct-reseller-payments.import.template',
                    ]),
                    self::permission('download reseller payments', 'Download reseller payment vouchers', [
                        'direct-reseller-payments.download',
                        'direct-reseller-payments.download-bulk',
                    ]),
                    self::permission('cancel reseller payments', 'Cancel reseller payments', ['direct-reseller-payments.cancel']),
                ],
            ],
            [
                'key' => 'products',
                'label' => 'Product Management',
                'permissions' => [
                    ...self::resourcePermissions('products', 'products', includeShow: true),
                    self::permission('manage direct product prices', 'Manage direct product prices'),
                    self::permission('manage reseller product prices', 'Manage reseller limit prices'),
                    self::permission('export products', 'Export products', ['products.export']),
                    self::permission('import products', 'Import products', [
                        'products.import.show',
                        'products.import.preview',
                        'products.import.store',
                        'products.import.template',
                    ]),
                    self::permission('print product barcodes', 'Print product barcodes', [
                        'products.barcode.bulk',
                        'products.barcode.print',
                        'products.success',
                    ]),
                    ...self::resourcePermissions('categories', 'categories', includeShow: true),
                    ...self::resourcePermissions('sub categories', 'sub-categories', includeShow: true),
                    ...self::resourcePermissions('units', 'units', includeShow: true),
                    ...self::resourcePermissions('attributes', 'attributes', includeShow: true),
                ],
            ],
            [
                'key' => 'orders',
                'label' => 'Order Management',
                'permissions' => [
                    self::permission('view orders', 'View orders', ['orders.index', 'orders.show']),
                    self::permission('create orders', 'Create orders', [
                        'orders.create',
                        'orders.store',
                        'orders.search-products',
                        'orders.search-resellers',
                        'orders.search-customers',
                    ]),
                    self::permission('edit orders', 'Edit orders', ['orders.edit', 'orders.update']),
                    self::permission('delete orders', 'Delete orders', ['orders.destroy']),
                    self::permission('update order statuses', 'Update order statuses', ['orders.status.update']),
                    self::permission('export orders', 'Export orders', ['orders.export', 'orders.pdf', 'orders.bulk-pdf']),
                    self::permission('print orders', 'Print orders', ['orders.print']),
                    self::permission('view order call list', 'View order call list', ['orders.call-list']),
                    self::permission('view waybills', 'View waybill queues', ['orders.waybill.index', 'orders.waybill.show']),
                    self::permission('print waybills', 'Print and reprint waybills', [
                        'orders.waybill.print',
                        'orders.waybill.reprint',
                        'orders.waybill.reprint-bulk',
                    ]),
                    self::permission('view waybill excel exports', 'View waybill Excel export queues', [
                        'orders.waybill-excel.index',
                        'orders.waybill-excel.show',
                    ]),
                    self::permission('export waybill excel', 'Export waybill Excel files', ['orders.waybill-excel.export']),
                    self::permission('view packing', 'View packing queues', [
                        'orders.packing.index',
                        'orders.packing.ready',
                        'orders.packing.picking',
                        'orders.packing.packed',
                        'orders.packing.pick-grn',
                        'orders.packing.process',
                    ]),
                    self::permission('process packing', 'Scan and process packing', [
                        'orders.packing.scan',
                        'orders.packing.mark-picked',
                        'orders.packing.mark-packed',
                    ]),
                    self::permission('dispatch orders', 'Mark packed orders dispatched', ['orders.packing.mark-dispatched']),
                ],
            ],
            [
                'key' => 'purchases',
                'label' => 'Purchases & Stock Intake',
                'permissions' => [
                    self::permission('view purchases', 'View purchases', [
                        'purchases.index',
                        'purchases.show',
                        'purchases.success',
                        'purchases.search-products',
                        'purchases.search-suppliers',
                    ]),
                    self::permission('create purchases', 'Create purchases', ['purchases.create', 'purchases.store']),
                    self::permission('edit purchases', 'Edit purchases', ['purchases.edit', 'purchases.update']),
                    self::permission('delete purchases', 'Delete purchases', ['purchases.destroy']),
                    self::permission('print purchases', 'Print purchase PDFs and barcodes', [
                        'purchases.pdf',
                        'purchases.barcodes',
                        'purchases.items.barcodes',
                    ]),
                    self::permission('view purchase moderation', 'View purchase moderation', [
                        'purchases.moderation.index',
                        'purchases.moderation.checking',
                        'purchases.moderation.verifying',
                        'purchases.moderation.grn',
                    ]),
                    self::permission('approve purchases', 'Approve purchase moderation stages', ['purchases.moderation.approve']),
                    self::permission('scan purchase grn', 'Scan purchase GRN units', [
                        'purchases.grn.show',
                        'purchases.grn.scan',
                    ]),
                    self::permission('view store placement', 'View store placement pages', ['purchases.store-placement.index']),
                    self::permission('place stock in stores', 'Place stock into retail or warehouse racks', [
                        'purchases.store-placement.store',
                        'purchases.store-placement.scan',
                    ]),
                    self::permission('view store racks', 'View retail and warehouse racks', ['purchases.store-racks.index']),
                    self::permission('create store racks', 'Create retail and warehouse racks', ['purchases.store-racks.store']),
                ],
            ],
            [
                'key' => 'couriers',
                'label' => 'Courier & Bank Accounts',
                'permissions' => [
                    ...self::resourcePermissions('couriers', 'couriers', includeShow: true),
                    self::permission('manage courier waybills', 'Manage courier waybill number ranges', [
                        'couriers.waybills.index',
                        'couriers.waybills.store',
                    ]),
                    self::permission('view courier receive', 'View receive courier payment flow', [
                        'courier-receive.index',
                        'courier-receive.show',
                        'courier-receive.search-order',
                    ]),
                    self::permission('process courier receive', 'Import and store courier receive payments', [
                        'courier-receive.import',
                        'courier-receive.store',
                    ]),
                    self::permission('view courier payments', 'View courier payment records', [
                        'courier-payments.index',
                        'courier-payments.show',
                        'courier-payments.create',
                    ]),
                    self::permission('edit courier payments', 'Edit courier payment records', [
                        'courier-payments.edit',
                        'courier-payments.update',
                    ]),
                    ...self::resourcePermissions('bank accounts', 'bank-accounts', includeShow: false),
                ],
            ],
            [
                'key' => 'reports',
                'label' => 'Reports',
                'permissions' => [
                    self::permission('view reports', 'View reports', [
                        'reports.index',
                        'reports.stock',
                        'reports.stock.show',
                        'reports.packet-count',
                        'reports.product-sales',
                        'reports.user-sales',
                        'reports.province',
                        'reports.profit-loss',
                    ]),
                    self::permission('export reports', 'Export reports to PDF or Excel'),
                ],
            ],
        ];
    }

    public static function groupedForDisplay(?Collection $permissions = null): array
    {
        $permissionRecords = $permissions?->keyBy('name') ?? collect();

        return collect(self::groups())
            ->map(function (array $group) use ($permissionRecords) {
                $group['permissions'] = collect($group['permissions'])
                    ->map(function (array $permission) use ($permissionRecords) {
                        $record = $permissionRecords->get($permission['name']);

                        return [
                            ...$permission,
                            'id' => $record?->id,
                        ];
                    })
                    ->values()
                    ->all();

                return $group;
            })
            ->all();
    }

    private static function resourcePermissions(string $label, string $routePrefix, bool $includeShow = false): array
    {
        $viewRoutes = [$routePrefix.'.index'];
        if ($includeShow) {
            $viewRoutes[] = $routePrefix.'.show';
        }

        $createRoutes = [$routePrefix.'.create', $routePrefix.'.store'];
        $createRoutes = [
            ...$createRoutes,
            ...match ($routePrefix) {
                'categories' => ['quick.category.store'],
                'sub-categories' => ['quick.subcategory.store'],
                'units' => ['quick.unit.store'],
                default => [],
            },
        ];

        return [
            self::permission('view '.$label, 'View '.str($label)->title(), $viewRoutes),
            self::permission('create '.$label, 'Create '.str($label)->title(), $createRoutes),
            self::permission('edit '.$label, 'Edit '.str($label)->title(), [$routePrefix.'.edit', $routePrefix.'.update']),
            self::permission(
                'delete '.$label,
                'Delete '.str($label)->title(),
                $routePrefix === 'products' ? [$routePrefix.'.destroy', 'products.destroy.bulk'] : [$routePrefix.'.destroy']
            ),
        ];
    }

    private static function paymentPermissions(string $label, string $routePrefix, bool $includeShow = false): array
    {
        $viewRoutes = [$routePrefix.'.index'];
        if ($includeShow) {
            $viewRoutes[] = $routePrefix.'.show';
        }

        return [
            self::permission('view '.$label, 'View '.str($label)->title(), $viewRoutes),
            self::permission('create '.$label, 'Create '.str($label)->title(), [$routePrefix.'.create', $routePrefix.'.store']),
            self::permission('edit '.$label, 'Edit '.str($label)->title(), [$routePrefix.'.edit', $routePrefix.'.update']),
        ];
    }

    private static function permission(string $name, string $label, array $routes = []): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'routes' => $routes,
        ];
    }
}
