<?php

namespace App\Http\Controllers\Admin;

use App\Support\RbacPermissions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::query();
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        $permissions = $query->paginate(10);
        $systemPermissionNames = RbacPermissions::allPermissionNames();

        return view('admin.permissions.index', compact('permissions', 'systemPermissionNames'));
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions'],
        ]);

        Permission::create(['name' => $request->name]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        if (in_array($permission->name, RbacPermissions::allPermissionNames(), true)) {
            return back()
                ->withInput()
                ->with('error', 'System permissions are managed by the RBAC catalog and cannot be renamed.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name,' . $permission->id],
        ]);

        $permission->update(['name' => $request->name]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        if (in_array($permission->name, RbacPermissions::allPermissionNames(), true)) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'System permissions are managed by the RBAC catalog and cannot be deleted.');
        }

        $permission->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}
