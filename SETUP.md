# Quick Setup Guide

## Initial Setup

1. **Clone and Install**
   ```bash
   git clone https://github.com/codezelat/shoppy-max.git
   cd shoppy-max
   composer install
   npm install
   ```

2. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Setup Database**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Build and Serve**
   ```bash
   npm run build
   php artisan serve
   ```

## Default Login

- **URL:** http://localhost:8000/login
- **Email:** admin@shoppy-max.com
- **Password:** password

## Admin Features

Once logged in as super admin, you can:

1. **Manage Users** - Create, edit, delete users with custom roles and permissions
2. **Manage Roles** - Create custom roles and assign permissions
3. **Manage Permissions** - Create new permissions for granular access control

## Creating Your First User

1. Log in with default super admin credentials
2. Click "Users" in the navigation menu
3. Click "Create User"
4. Fill in the form:
   - Name: Enter full name
   - Email: Enter email address
   - Password: Enter secure password
   - Confirm Password: Re-enter password
   - Roles: Select one or more roles
   - Permissions: Select additional permissions (optional)
5. Click "Create User"

## Creating Custom Roles

1. Click "Roles" in the navigation menu
2. Click "Create Role"
3. Enter role name (e.g., "Manager")
4. Select permissions for this role
5. Click "Create Role"

## Creating Custom Permissions

1. Click "Permissions" in the navigation menu
2. Click "Create Permission"
3. Enter permission name (e.g., "manage products")
4. Click "Create Permission"

## Available Routes

- `/` - Welcome page
- `/login` - Login page
- `/register` - Registration page
- `/dashboard` - User dashboard (requires authentication)
- `/admin/users` - User management (super admin only)
- `/admin/roles` - Role management (super admin only)
- `/admin/permissions` - Permission management (super admin only)

## Tips

- Always assign at least one role to users
- Super admin role has all permissions by default
- You cannot delete yourself from the user management panel
- Default roles (super admin, admin, user) cannot be deleted
- Permissions assigned directly to users override role permissions

## Troubleshooting

**Cannot login:**
- Ensure database is seeded: `php artisan db:seed`
- Clear cache: `php artisan cache:clear`

**Permission denied:**
- Ensure user has correct role assigned
- Check if role has required permissions

**Assets not loading:**
- Run: `npm run build`
- Clear browser cache

## Next Steps

1. Customize the application for your needs
2. Add more permissions based on your features
3. Create additional roles for different user types
4. Implement your business logic
5. Add more modules and features as needed
