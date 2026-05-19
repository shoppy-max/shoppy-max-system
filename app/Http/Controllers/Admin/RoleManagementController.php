<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\RbacPermissions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::with('permissions');
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        $roles = $query->paginate(10);
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissionGroups = RbacPermissions::groupedForDisplay(Permission::orderBy('name')->get());

        return view('admin.roles.create', compact('permissionGroups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if ($request->has('permissions') && ! $request->user()?->can('assign permissions')) {
            abort(403);
        }

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $permissionGroups = RbacPermissions::groupedForDisplay(Permission::orderBy('name')->get());
        $selectedPermissions = $role->permissions->pluck('name')->toArray();
        
        return view('admin.roles.edit', compact('role', 'permissionGroups', 'selectedPermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if ($request->has('permissions') && ! $request->user()?->can('assign permissions')) {
            abort(403);
        }

        if ($role->name === 'super admin' && $request->name !== 'super admin') {
            return back()
                ->withInput()
                ->with('error', 'The super admin role name cannot be changed.');
        }

        $role->update(['name' => $request->name]);

        if ($request->user()?->can('assign permissions')) {
            if ($role->name === 'super admin') {
                $role->syncPermissions(RbacPermissions::allPermissionNames());
            } else {
                $role->syncPermissions($request->input('permissions', []));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, ['super admin', 'admin', 'user'])) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot delete default roles.');
        }

        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
