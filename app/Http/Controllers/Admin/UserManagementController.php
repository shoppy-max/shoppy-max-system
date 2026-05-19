<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Support\RbacPermissions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Spatie\Permission\PermissionRegistrar;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        
        $users = $query->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $permissionGroups = RbacPermissions::groupedForDisplay(Permission::orderBy('name')->get());

        return view('admin.users.create', compact('roles', 'permissionGroups'));
    }

    public function store(Request $request)
    {
        if (($request->has('roles') || $request->has('permissions')) && ! $request->user()?->can('assign permissions')) {
            abort(403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->has('roles')) {
            $user->assignRole($request->roles);
        }

        if ($request->has('permissions')) {
            $user->givePermissionTo($request->permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $permissionGroups = RbacPermissions::groupedForDisplay(Permission::orderBy('name')->get());
        $userRoles = $user->roles->pluck('id')->toArray();
        $userPermissions = $user->permissions->pluck('name')->toArray();
        
        return view('admin.users.edit', compact('user', 'roles', 'permissionGroups', 'userRoles', 'userPermissions'));
    }

    public function update(Request $request, User $user)
    {
        if (($request->has('roles') || $request->has('permissions')) && ! $request->user()?->can('assign permissions')) {
            abort(403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        if ($request->user()?->can('assign permissions')) {
            $user->syncRoles($request->input('roles', []));
            $user->syncPermissions($request->input('permissions', []));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
