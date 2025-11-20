# Shoppy Max - Laravel E-Commerce Application

A modern e-commerce application built with Laravel 12, featuring comprehensive authentication and role-based access control (RBAC) system.

## 🚀 Features

- **Latest Laravel 12.39.0** - Modern PHP framework
- **Laravel Breeze Authentication** - Complete authentication scaffolding with Blade templates
- **Role-Based Access Control** - Powered by Spatie Laravel Permission
- **User Management** - Full CRUD for user management
- **Role Management** - Create and manage roles with custom permissions
- **Permission Management** - Granular permission control
- **Super Admin Panel** - Complete admin interface for managing users, roles, and permissions
- **Responsive Design** - Built with Tailwind CSS

## 📋 Prerequisites

- PHP >= 8.2
- Composer
- Node.js & NPM
- SQLite (default) or MySQL/PostgreSQL

## 🛠️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/codezelat/shoppy-max.git
   cd shoppy-max
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Create environment file**
   ```bash
   cp .env.example .env
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Run database migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed the database with default roles and permissions**
   ```bash
   php artisan db:seed
   ```

8. **Build assets**
   ```bash
   npm run build
   ```

9. **Start the development server**
   ```bash
   php artisan serve
   ```

Visit `http://localhost:8000` in your browser.

## 👤 Default Credentials

After seeding the database, you can log in with:

- **Email:** admin@shoppy-max.com
- **Password:** password
- **Role:** Super Admin

## 🎯 User Roles & Permissions

### Default Roles

1. **Super Admin** - Full access to all features
2. **Admin** - Limited administrative access
3. **User** - Basic user access

### Default Permissions

- `view users` - View user list
- `create users` - Create new users
- `edit users` - Edit existing users
- `delete users` - Delete users
- `view roles` - View roles
- `create roles` - Create new roles
- `edit roles` - Edit existing roles
- `delete roles` - Delete roles
- `view permissions` - View permissions
- `assign permissions` - Assign permissions to users/roles

## 📱 Admin Panel Features

### User Management (`/admin/users`)
- Create users with custom roles and permissions
- Edit user details and reassign roles
- Delete users (except self)
- View all users with their assigned roles

### Role Management (`/admin/roles`)
- Create custom roles
- Assign permissions to roles
- Edit role permissions
- Delete custom roles (default roles are protected)

### Permission Management (`/admin/permissions`)
- Create new permissions
- Edit permission names
- Delete permissions
- View all available permissions

## 🔐 Security Features

- Password hashing with bcrypt
- CSRF protection
- SQL injection protection
- XSS protection
- Role-based middleware
- Permission-based authorization

## 🏗️ Project Structure

```
shoppy-max/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Admin/
│   │           ├── UserManagementController.php
│   │           ├── RoleManagementController.php
│   │           └── PermissionManagementController.php
│   └── Models/
│       └── User.php (with HasRoles trait)
├── database/
│   ├── migrations/
│   │   └── create_permission_tables.php
│   └── seeders/
│       └── RolesAndPermissionsSeeder.php
├── resources/
│   └── views/
│       ├── admin/
│       │   ├── users/
│       │   ├── roles/
│       │   └── permissions/
│       └── auth/
└── routes/
    └── web.php
```

## 🧪 Testing

Run the test suite:

```bash
php artisan test
```

## 📦 Packages Used

- [Laravel Framework](https://laravel.com) - PHP Framework
- [Laravel Breeze](https://laravel.com/docs/starter-kits) - Authentication scaffolding
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) - Role and Permission management
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework

## 🔧 Configuration

### Database Configuration

By default, the application uses SQLite. To use MySQL or PostgreSQL, update your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shoppy_max
DB_USERNAME=root
DB_PASSWORD=
```

### Mail Configuration

Configure your mail settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
```

## 📖 Usage Examples

### Creating a New User (Super Admin)

1. Log in as super admin
2. Navigate to "Users" in the navigation menu
3. Click "Create User"
4. Fill in the user details
5. Select roles and permissions
6. Click "Create User"

### Assigning Permissions to a Role

1. Navigate to "Roles" in the navigation menu
2. Click "Edit" on the desired role
3. Select the permissions to assign
4. Click "Update Role"

### Creating Custom Permissions

1. Navigate to "Permissions" in the navigation menu
2. Click "Create Permission"
3. Enter the permission name (e.g., "edit products")
4. Click "Create Permission"

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👥 Credits

Built with ❤️ using Laravel and Spatie Permission package.

## 📞 Support

For support, email your-email@example.com or create an issue in the repository.
