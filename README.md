# Shoppy Max - Laravel Inventory & Contact Management System

A modern inventory and contact management application built with Laravel 12. This system features a comprehensive role-based access control (RBAC) system, contact management for Customers, Suppliers, and Resellers, and a streamlined user interface.

## 🚀 Features

- **Latest Laravel 12.39.0** - Built on the modern PHP framework.
- **Authentication & Security** - Laravel Breeze authentication with role-based access control (Spatie Permission).
- **Contact Management Module** - Dedicated management for:
    - **Customers**: Manage customer details (Name, Phone, Address).
    - **Suppliers**: Track supplier information including business name and due amounts.
    - **Resellers**: Manage reseller profiles and accounts.
    - **Cities**: Manage locations/cities for logistical purposes.
- **Sidebar Navigation** - specialized sidebar for easy access to all modules.
- **User Management** - Full CRUD for managing system users.
- **Role & Permission Management** - Granular control over system access.
- **Responsive Design** - Clean UI built with Tailwind CSS.

## 📋 Prerequisites

- PHP >= 8.2
- Composer
- Node.js & NPM
- SQLite (default) or MySQL/PostgreSQL

## 🛠️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/codezelat/shoppy-max-v01.git
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

4. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database Setup**
   Ensure your database configuration in `.env` is correct. Then run migrations and seeds:
   ```bash
   php artisan migrate:fresh --seed
   ```
   This seeds:
   - RBAC roles/permissions
   - Default Super Admin user
   - Core master data (units, categories, sub categories, cities, couriers, bank accounts)
   - Linked demo transactional data (products/variants, resellers/direct resellers, suppliers, orders, payments, purchases)

6. **Build Assets**
   ```bash
   npm run build
   ```

7. **Start Server**
   ```bash
   php artisan serve
   ```

   Visit `http://localhost:8000` in your browser.

## 👤 Default Credentials

- **Email:** `admin@shoppy-max.com`
- **Password:** `password`
- **Role:** Super Admin

Optional demo manager user:
- **Email:** `manager@shoppy-max.com`
- **Password:** `password`
- **Role:** Admin

## 📱 Application Modules

### Contact Management
- **Customers**: `/customers` - View, Add, Edit, and Delete customers.
- **Suppliers**: `/suppliers` - Manage supplier details and due amounts.
- **Resellers**: `/resellers` - Manage reseller business info and dues.
- **Cities**: `/cities` - Manage city and district information.

### Admin Panel
- **Users**: `/admin/users` - Manage application users.
- **Roles**: `/admin/roles` - Define user roles.
- **Permissions**: `/admin/permissions` - Configure access rights.

## 🏗️ Project Structure

```
shoppy-max/
├── app/
│   ├── Http/Controllers/       # Application Controllers
│   │   ├── Admin/              # User/Role Management
│   │   └── (Resource Controllers for Contacts)
│   └── Models/                 # Eloquent Models (User, Customer, Supplier, etc.)
├── database/                   # Migrations and Seeders
├── resources/
│   └── views/
│       ├── contacts/           # Contact Management Views
│       ├── layouts/            # App Layouts (including new Sidebar)
│       └── admin/              # Admin Panel Views
└── routes/
    └── web.php                 # App Routes
```

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👥 Credits

Built with ❤️ using Laravel, Tailwind CSS, and Spatie Permission.
