<?php

namespace App\Http\Controllers\Admin;

use App\Support\RbacPermissions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionManagementController extends Controller
{
    public function index(Request $request)
    {
        $systemPermissionNames = RbacPermissions::allPermissionNames();
        $permissionMeta = collect(RbacPermissions::groups())
            ->flatMap(fn (array $group) => collect($group['permissions'])
                ->mapWithKeys(fn (array $permission) => [
                    $permission['name'] => [
                        'label' => $permission['label'],
                        'group' => $group['label'],
                    ],
                ]));

        $query = Permission::query()
            ->whereIn('name', $systemPermissionNames);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $matchingNames = collect($systemPermissionNames)
                ->filter(function (string $permissionName) use ($permissionMeta, $search): bool {
                    $meta = $permissionMeta->get($permissionName, []);

                    return str_contains(strtolower($permissionName), strtolower($search))
                        || str_contains(strtolower($meta['label'] ?? ''), strtolower($search))
                        || str_contains(strtolower($meta['group'] ?? ''), strtolower($search));
                })
                ->values()
                ->all();

            $query->whereIn('name', $matchingNames);
        }
        
        $permissions = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.permissions.index', compact('permissions', 'systemPermissionNames', 'permissionMeta'));
    }
}
