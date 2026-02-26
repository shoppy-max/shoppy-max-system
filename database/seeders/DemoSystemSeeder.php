<?php

namespace Database\Seeders;

use App\Models\Attribute as ProductAttribute;
use App\Models\AttributeValue;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\City;
use App\Models\Courier;
use App\Models\CourierPayment;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Reseller;
use App\Models\ResellerPayment;
use App\Models\ResellerTarget;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSystemSeeder extends Seeder
{
    public function run(): void
    {
        $users = $this->seedUsers();
        $units = $this->seedUnits();
        [$categories, $subCategories] = $this->seedCategories();
        $cities = $this->seedCities();
        $couriers = $this->seedCouriers();
        $bankAccounts = $this->seedBankAccounts();
        $suppliers = $this->seedSuppliers();
        $resellers = $this->seedResellers($couriers);
        $this->seedResellerTargets($resellers);
        [$products, $variants] = $this->seedProducts($categories, $subCategories, $units);
        $customers = $this->seedCustomers($cities);
        $orders = $this->seedOrders($users, $resellers, $customers, $couriers, $cities, $variants);
        $this->seedCourierPayments($users, $couriers, $bankAccounts, $orders);
        $this->seedResellerPayments($resellers);
        $this->seedPurchases($suppliers, $products);
        $this->syncResellerDueAmounts($resellers);
        $this->seedAttributes();
    }

    private function seedUsers(): array
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@shoppy-max.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'phone' => '0710000000',
                'email_verified_at' => now(),
            ]
        );

        if (!$superAdmin->wasRecentlyCreated) {
            $superAdmin->name = 'Super Admin';
            $superAdmin->password = 'password';
            $superAdmin->phone = $superAdmin->phone ?: '0710000000';
            $superAdmin->email_verified_at = now();
            $superAdmin->save();
        }

        $manager = User::updateOrCreate(
            ['email' => 'manager@shoppy-max.com'],
            [
                'name' => 'Operations Manager',
                'password' => Hash::make('password'),
                'phone' => '0710000001',
                'email_verified_at' => now(),
            ]
        );

        $superAdminRole = \Spatie\Permission\Models\Role::query()->where('name', 'super admin')->first();
        $adminRole = \Spatie\Permission\Models\Role::query()->where('name', 'admin')->first();

        if ($superAdminRole) {
            $superAdmin->syncRoles([$superAdminRole->name]);
        }
        if ($adminRole) {
            $manager->syncRoles([$adminRole->name]);
        }

        return [
            'super_admin' => $superAdmin,
            'manager' => $manager,
        ];
    }

    private function seedUnits(): array
    {
        $rows = [
            ['name' => 'Piece', 'short_name' => 'pcs'],
            ['name' => 'Milliliter', 'short_name' => 'ml'],
            ['name' => 'Liter', 'short_name' => 'l'],
            ['name' => 'Gram', 'short_name' => 'g'],
            ['name' => 'Kilogram', 'short_name' => 'kg'],
        ];

        $map = [];
        foreach ($rows as $row) {
            $unit = Unit::updateOrCreate(
                ['short_name' => $row['short_name']],
                ['name' => $row['name']]
            );
            $map[$row['short_name']] = $unit;
        }

        return $map;
    }

    private function seedCategories(): array
    {
        $categoryRows = [
            ['name' => 'Beauty', 'code' => 'BEAUTY'],
            ['name' => 'Grocery', 'code' => 'GROC'],
            ['name' => 'Electronics', 'code' => 'ELEC'],
        ];

        $categories = [];
        foreach ($categoryRows as $row) {
            $category = Category::updateOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code']]
            );
            $categories[$row['name']] = $category;
        }

        $subCategoryRows = [
            ['category' => 'Beauty', 'name' => 'Hair Care'],
            ['category' => 'Beauty', 'name' => 'Skin Care'],
            ['category' => 'Grocery', 'name' => 'Dry Goods'],
            ['category' => 'Grocery', 'name' => 'Beverages'],
            ['category' => 'Electronics', 'name' => 'Mobile Accessories'],
            ['category' => 'Electronics', 'name' => 'Computer Accessories'],
        ];

        $subCategories = [];
        foreach ($subCategoryRows as $row) {
            $subCategory = SubCategory::updateOrCreate(
                [
                    'category_id' => $categories[$row['category']]->id,
                    'name' => $row['name'],
                ],
                []
            );
            $subCategories[$row['name']] = $subCategory;
        }

        return [$categories, $subCategories];
    }

    private function seedCities(): array
    {
        $rows = [
            ['city_name' => 'Colombo 01', 'postal_code' => '00100', 'district' => 'Colombo', 'province' => 'Western'],
            ['city_name' => 'Nugegoda', 'postal_code' => '10250', 'district' => 'Colombo', 'province' => 'Western'],
            ['city_name' => 'Maharagama', 'postal_code' => '10280', 'district' => 'Colombo', 'province' => 'Western'],
            ['city_name' => 'Kandy', 'postal_code' => '20000', 'district' => 'Kandy', 'province' => 'Central'],
            ['city_name' => 'Galle', 'postal_code' => '80000', 'district' => 'Galle', 'province' => 'Southern'],
            ['city_name' => 'Matara', 'postal_code' => '81000', 'district' => 'Matara', 'province' => 'Southern'],
        ];

        $map = [];
        foreach ($rows as $row) {
            $city = City::updateOrCreate(
                [
                    'city_name' => $row['city_name'],
                    'district' => $row['district'],
                ],
                [
                    'postal_code' => $row['postal_code'],
                    'province' => $row['province'],
                ]
            );

            $map[$row['city_name'] . '|' . $row['district']] = $city;
        }

        return $map;
    }

    private function seedCouriers(): array
    {
        $rows = [
            [
                'name' => 'SpeedX Courier',
                'phone' => '0112300001',
                'email' => 'ops@speedx.example',
                'address' => 'No 10, Colombo',
                'rates' => ['300.00', '500.00', '800.00'],
                'is_active' => true,
            ],
            [
                'name' => 'Prompt Express',
                'phone' => '0112300002',
                'email' => 'ops@prompt.example',
                'address' => 'No 15, Kandy',
                'rates' => ['250.00', '450.00', '700.00'],
                'is_active' => true,
            ],
            [
                'name' => 'Lanka Post Parcel',
                'phone' => '0112300003',
                'email' => 'ops@lankapost.example',
                'address' => 'No 20, Galle',
                'rates' => ['200.00', '400.00', '650.00'],
                'is_active' => true,
            ],
        ];

        $map = [];
        foreach ($rows as $row) {
            $courier = Courier::updateOrCreate(
                ['name' => $row['name']],
                [
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'address' => $row['address'],
                    'rates' => $row['rates'],
                    'is_active' => $row['is_active'],
                ]
            );
            $map[$row['name']] = $courier;
        }

        return $map;
    }

    private function seedBankAccounts(): array
    {
        $rows = [
            [
                'name' => 'Main Operations',
                'bank_name' => 'Commercial Bank',
                'account_number' => '778812340001',
                'holder_name' => 'Shoppy Max Pvt Ltd',
                'type' => 'Bank',
                'note' => 'Primary operations account',
                'is_active' => true,
            ],
            [
                'name' => 'Courier Float Wallet',
                'bank_name' => null,
                'account_number' => 'CFW-0001',
                'holder_name' => 'Shoppy Max Logistics',
                'type' => 'Cash',
                'note' => 'Used for courier settlements',
                'is_active' => true,
            ],
        ];

        $map = [];
        foreach ($rows as $row) {
            $account = BankAccount::updateOrCreate(
                ['account_number' => $row['account_number']],
                [
                    'name' => $row['name'],
                    'bank_name' => $row['bank_name'],
                    'holder_name' => $row['holder_name'],
                    'type' => $row['type'],
                    'note' => $row['note'],
                    'is_active' => $row['is_active'],
                ]
            );
            $map[$row['name']] = $account;
        }

        return $map;
    }

    private function seedSuppliers(): array
    {
        $rows = [
            [
                'business_name' => 'Nova Imports',
                'name' => 'Dilshan Fernando',
                'email' => 'contact@novaimports.example',
                'mobile' => '0777000101',
                'landline' => '0112800001',
                'address' => 'No 45, Main Street',
                'city' => 'Colombo 01',
                'district' => 'Colombo',
                'province' => 'Western',
                'country' => 'Sri Lanka',
                'due_amount' => 0,
            ],
            [
                'business_name' => 'GreenLeaf Distributors',
                'name' => 'Nuwan Perera',
                'email' => 'sales@greenleaf.example',
                'mobile' => '0777000102',
                'landline' => '0112800002',
                'address' => 'No 12, Industrial Zone',
                'city' => 'Kandy',
                'district' => 'Kandy',
                'province' => 'Central',
                'country' => 'Sri Lanka',
                'due_amount' => 0,
            ],
        ];

        $map = [];
        foreach ($rows as $row) {
            $supplier = Supplier::updateOrCreate(
                ['business_name' => $row['business_name']],
                $row
            );
            $map[$row['business_name']] = $supplier;
        }

        return $map;
    }

    private function seedResellers(array $couriers): array
    {
        $rows = [
            [
                'business_name' => 'Glow Wholesale Traders',
                'name' => 'Nimal Silva',
                'email' => 'nimal@glowwholesale.example',
                'mobile' => '0778100001',
                'landline' => '0112900001',
                'address' => 'No 5, Market Road',
                'city' => 'Colombo 01',
                'district' => 'Colombo',
                'province' => 'Western',
                'country' => 'Sri Lanka',
                'reseller_type' => Reseller::TYPE_RESELLER,
                'return_fee' => 150.00,
                'couriers' => ['SpeedX Courier', 'Prompt Express'],
            ],
            [
                'business_name' => 'Metro Gadgets Distribution',
                'name' => 'Saman Jayasinghe',
                'email' => 'saman@metrogadgets.example',
                'mobile' => '0778100002',
                'landline' => '0112900002',
                'address' => 'No 88, Tech Park',
                'city' => 'Kandy',
                'district' => 'Kandy',
                'province' => 'Central',
                'country' => 'Sri Lanka',
                'reseller_type' => Reseller::TYPE_RESELLER,
                'return_fee' => 125.00,
                'couriers' => ['Prompt Express', 'Lanka Post Parcel'],
            ],
            [
                'business_name' => 'Daily Choice Resellers',
                'name' => 'Iresha Dissanayake',
                'email' => 'iresha@dailychoice.example',
                'mobile' => '0778100003',
                'landline' => '0112900003',
                'address' => 'No 22, New Lane',
                'city' => 'Galle',
                'district' => 'Galle',
                'province' => 'Southern',
                'country' => 'Sri Lanka',
                'reseller_type' => Reseller::TYPE_RESELLER,
                'return_fee' => 100.00,
                'couriers' => ['SpeedX Courier'],
            ],
            [
                'business_name' => 'Bright Cart Direct',
                'name' => 'Kasun Madusha',
                'email' => 'kasun@brightcart.example',
                'mobile' => '0778200001',
                'landline' => '0112910001',
                'address' => 'No 74, Lake Road',
                'city' => 'Maharagama',
                'district' => 'Colombo',
                'province' => 'Western',
                'country' => 'Sri Lanka',
                'reseller_type' => Reseller::TYPE_DIRECT_RESELLER,
                'return_fee' => 175.00,
                'couriers' => ['Lanka Post Parcel', 'Prompt Express'],
            ],
            [
                'business_name' => 'Home Essentials Direct',
                'name' => 'Tharindu Ranasinghe',
                'email' => 'tharindu@homeessentials.example',
                'mobile' => '0778200002',
                'landline' => '0112910002',
                'address' => 'No 101, Garden Avenue',
                'city' => 'Matara',
                'district' => 'Matara',
                'province' => 'Southern',
                'country' => 'Sri Lanka',
                'reseller_type' => Reseller::TYPE_DIRECT_RESELLER,
                'return_fee' => 160.00,
                'couriers' => ['SpeedX Courier'],
            ],
        ];

        $map = [];
        foreach ($rows as $row) {
            $courierNames = $row['couriers'];
            unset($row['couriers']);
            $row['due_amount'] = 0;

            $reseller = Reseller::updateOrCreate(
                [
                    'business_name' => $row['business_name'],
                    'reseller_type' => $row['reseller_type'],
                ],
                $row
            );

            $courierIds = collect($courierNames)
                ->map(fn ($name) => $couriers[$name]->id ?? null)
                ->filter()
                ->values()
                ->all();

            $reseller->couriers()->sync($courierIds);
            $map[$row['business_name']] = $reseller;
        }

        return $map;
    }

    private function seedResellerTargets(array $resellers): void
    {
        $targets = [
            [
                'reseller' => 'Glow Wholesale Traders',
                'target_type' => 'monthly',
                'target_completed_price' => 150000,
                'with_completed_price' => 140000,
                'return_order_target_price' => 10000,
                'target_pcs_qty' => 120,
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'ref_id' => 'TG-DEMO-001',
            ],
            [
                'reseller' => 'Metro Gadgets Distribution',
                'target_type' => 'monthly',
                'target_completed_price' => 180000,
                'with_completed_price' => 165000,
                'return_order_target_price' => 12000,
                'target_pcs_qty' => 140,
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'ref_id' => 'TG-DEMO-002',
            ],
        ];

        foreach ($targets as $target) {
            $reseller = $resellers[$target['reseller']] ?? null;
            if (!$reseller || $reseller->reseller_type !== Reseller::TYPE_RESELLER) {
                continue;
            }

            ResellerTarget::updateOrCreate(
                [
                    'reseller_id' => $reseller->id,
                    'ref_id' => $target['ref_id'],
                ],
                [
                    'target_type' => $target['target_type'],
                    'target_completed_price' => $target['target_completed_price'],
                    'with_completed_price' => $target['with_completed_price'],
                    'return_order_target_price' => $target['return_order_target_price'],
                    'target_pcs_qty' => $target['target_pcs_qty'],
                    'start_date' => $target['start_date'],
                    'end_date' => $target['end_date'],
                ]
            );
        }
    }

    private function seedProducts(array $categories, array $subCategories, array $units): array
    {
        $rows = [
            [
                'name' => 'Herbal Shine Shampoo',
                'category' => 'Beauty',
                'sub_category' => 'Hair Care',
                'description' => 'Daily use herbal shampoo.',
                'warranty_period' => null,
                'warranty_period_type' => null,
                'variants' => [
                    ['sku' => 'HSH-250ML', 'unit' => 'ml', 'unit_value' => '250', 'selling_price' => 1250, 'limit_price' => 980, 'quantity' => 40, 'alert_quantity' => 8],
                    ['sku' => 'HSH-500ML', 'unit' => 'ml', 'unit_value' => '500', 'selling_price' => 2200, 'limit_price' => 1800, 'quantity' => 18, 'alert_quantity' => 5],
                ],
            ],
            [
                'name' => 'Silk Touch Face Cream',
                'category' => 'Beauty',
                'sub_category' => 'Skin Care',
                'description' => 'Hydrating cream for dry skin.',
                'warranty_period' => null,
                'warranty_period_type' => null,
                'variants' => [
                    ['sku' => 'STF-50G', 'unit' => 'g', 'unit_value' => '50', 'selling_price' => 1650, 'limit_price' => 1320, 'quantity' => 30, 'alert_quantity' => 6],
                ],
            ],
            [
                'name' => 'Premium Nadu Rice',
                'category' => 'Grocery',
                'sub_category' => 'Dry Goods',
                'description' => 'Premium quality rice.',
                'warranty_period' => null,
                'warranty_period_type' => null,
                'variants' => [
                    ['sku' => 'PNR-1KG', 'unit' => 'kg', 'unit_value' => '1', 'selling_price' => 320, 'limit_price' => 260, 'quantity' => 120, 'alert_quantity' => 25],
                    ['sku' => 'PNR-5KG', 'unit' => 'kg', 'unit_value' => '5', 'selling_price' => 1500, 'limit_price' => 1250, 'quantity' => 55, 'alert_quantity' => 10],
                ],
            ],
            [
                'name' => 'FreshDay Orange Juice',
                'category' => 'Grocery',
                'sub_category' => 'Beverages',
                'description' => 'Orange drink pack.',
                'warranty_period' => null,
                'warranty_period_type' => null,
                'variants' => [
                    ['sku' => 'FDOJ-1L', 'unit' => 'l', 'unit_value' => '1', 'selling_price' => 780, 'limit_price' => 620, 'quantity' => 72, 'alert_quantity' => 12],
                ],
            ],
            [
                'name' => 'VoltLink USB-C Cable',
                'category' => 'Electronics',
                'sub_category' => 'Mobile Accessories',
                'description' => 'Fast charge USB-C cable.',
                'warranty_period' => 6,
                'warranty_period_type' => 'months',
                'variants' => [
                    ['sku' => 'VLC-1M', 'unit' => 'pcs', 'unit_value' => '1m', 'selling_price' => 1250, 'limit_price' => 950, 'quantity' => 65, 'alert_quantity' => 15],
                    ['sku' => 'VLC-2M', 'unit' => 'pcs', 'unit_value' => '2m', 'selling_price' => 1650, 'limit_price' => 1250, 'quantity' => 28, 'alert_quantity' => 8],
                ],
            ],
            [
                'name' => 'NovaSound Earbuds',
                'category' => 'Electronics',
                'sub_category' => 'Mobile Accessories',
                'description' => 'Bluetooth earbuds with charging case.',
                'warranty_period' => 1,
                'warranty_period_type' => 'years',
                'variants' => [
                    ['sku' => 'NSE-BLK', 'unit' => 'pcs', 'unit_value' => null, 'selling_price' => 7900, 'limit_price' => 6500, 'quantity' => 0, 'alert_quantity' => 4],
                ],
            ],
            [
                'name' => 'Luma Desk Lamp',
                'category' => 'Electronics',
                'sub_category' => 'Computer Accessories',
                'description' => 'LED desk lamp with adjustable arm.',
                'warranty_period' => 1,
                'warranty_period_type' => 'years',
                'variants' => [
                    ['sku' => 'LDL-STD', 'unit' => 'pcs', 'unit_value' => null, 'selling_price' => 3600, 'limit_price' => 2800, 'quantity' => 24, 'alert_quantity' => 5],
                ],
            ],
            [
                'name' => 'Shoppy Max Gift Voucher',
                'category' => 'Electronics',
                'sub_category' => null,
                'description' => 'Store voucher card.',
                'warranty_period' => null,
                'warranty_period_type' => null,
                'variants' => [
                    ['sku' => 'SMGV-1000', 'unit' => 'pcs', 'unit_value' => null, 'selling_price' => 1000, 'limit_price' => 900, 'quantity' => 80, 'alert_quantity' => 15],
                ],
            ],
        ];

        $productMap = [];
        $variantMap = [];

        foreach ($rows as $row) {
            $product = Product::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($row['name'])])
                ->first();

            $payload = [
                'name' => $row['name'],
                'category_id' => $categories[$row['category']]->id ?? null,
                'sub_category_id' => $row['sub_category'] ? ($subCategories[$row['sub_category']]->id ?? null) : null,
                'description' => $row['description'],
                'warranty_period' => $row['warranty_period'],
                'warranty_period_type' => $row['warranty_period_type'],
            ];

            if ($product) {
                $product->fill($payload);
                $product->save();
            } else {
                $product = Product::create($payload);
            }

            $productMap[$row['name']] = $product;

            foreach ($row['variants'] as $variant) {
                $unit = $units[$variant['unit']] ?? null;
                $variantModel = ProductVariant::updateOrCreate(
                    ['sku' => $variant['sku']],
                    [
                        'product_id' => $product->id,
                        'unit_id' => $unit?->id,
                        'unit_value' => $variant['unit_value'],
                        'selling_price' => $variant['selling_price'],
                        'limit_price' => $variant['limit_price'],
                        'alert_quantity' => $variant['alert_quantity'],
                        'quantity' => $variant['quantity'],
                    ]
                );

                $variantMap[$variant['sku']] = $variantModel;
            }
        }

        return [$productMap, $variantMap];
    }

    private function seedCustomers(array $cities): array
    {
        $rows = [
            [
                'name' => 'Amila Perera',
                'mobile' => '0771234567',
                'landline' => '0112345678',
                'address' => 'No 12, Flower Road',
                'city_key' => 'Colombo 01|Colombo',
            ],
            [
                'name' => 'Nadeesha Silva',
                'mobile' => '0712345678',
                'landline' => null,
                'address' => 'No 45, Hill Street',
                'city_key' => 'Kandy|Kandy',
            ],
            [
                'name' => 'Kavindi Jayasekara',
                'mobile' => '0761234567',
                'landline' => '0412345678',
                'address' => 'No 23, Beach Road',
                'city_key' => 'Galle|Galle',
            ],
            [
                'name' => 'Ruwan Maduranga',
                'mobile' => '0751234567',
                'landline' => null,
                'address' => 'No 90, Station Road',
                'city_key' => 'Maharagama|Colombo',
            ],
        ];

        $map = [];
        foreach ($rows as $row) {
            $city = $cities[$row['city_key']] ?? null;
            $customer = Customer::updateOrCreate(
                ['mobile' => $row['mobile']],
                [
                    'name' => $row['name'],
                    'landline' => $row['landline'],
                    'address' => $row['address'],
                    'country' => 'Sri Lanka',
                    'province' => $city?->province,
                    'district' => $city?->district,
                    'city' => $city?->city_name,
                ]
            );

            $map[$row['name']] = $customer;
        }

        return $map;
    }

    private function seedOrders(
        array $users,
        array $resellers,
        array $customers,
        array $couriers,
        array $cities,
        array $variants
    ): array {
        $rows = [
            [
                'order_number' => 'DEMO-ORD-0001',
                'order_date' => now()->subDays(5)->toDateString(),
                'order_type' => 'direct',
                'status' => 'confirm',
                'call_status' => 'confirm',
                'payment_method' => 'COD',
                'customer' => 'Amila Perera',
                'reseller' => null,
                'city_key' => 'Colombo 01|Colombo',
                'courier' => 'SpeedX Courier',
                'courier_charge' => 300.00,
                'sales_note' => 'Handle with care',
                'items' => [
                    ['sku' => 'VLC-1M', 'quantity' => 2, 'selling_price' => 1250.00],
                    ['sku' => 'LDL-STD', 'quantity' => 1, 'selling_price' => 3600.00],
                ],
            ],
            [
                'order_number' => 'DEMO-ORD-0002',
                'order_date' => now()->subDays(4)->toDateString(),
                'order_type' => 'reseller',
                'status' => 'pending',
                'call_status' => 'pending',
                'payment_method' => 'Bank Transfer',
                'customer' => 'Nadeesha Silva',
                'reseller' => 'Glow Wholesale Traders',
                'city_key' => 'Kandy|Kandy',
                'courier' => 'Prompt Express',
                'courier_charge' => 500.00,
                'sales_note' => 'Reseller bulk trial order',
                'items' => [
                    ['sku' => 'HSH-250ML', 'quantity' => 3, 'selling_price' => 1180.00],
                    ['sku' => 'FDOJ-1L', 'quantity' => 4, 'selling_price' => 760.00],
                ],
            ],
            [
                'order_number' => 'DEMO-ORD-0003',
                'order_date' => now()->subDays(3)->toDateString(),
                'order_type' => 'reseller',
                'status' => 'hold',
                'call_status' => 'confirm',
                'payment_method' => 'Online Payment',
                'customer' => 'Kavindi Jayasekara',
                'reseller' => 'Bright Cart Direct',
                'city_key' => 'Galle|Galle',
                'courier' => 'Lanka Post Parcel',
                'courier_charge' => 400.00,
                'sales_note' => 'Direct reseller order',
                'items' => [
                    ['sku' => 'PNR-1KG', 'quantity' => 5, 'selling_price' => 310.00],
                    ['sku' => 'SMGV-1000', 'quantity' => 2, 'selling_price' => 1000.00],
                ],
            ],
            [
                'order_number' => 'DEMO-ORD-0004',
                'order_date' => now()->subDays(2)->toDateString(),
                'order_type' => 'direct',
                'status' => 'cancelled',
                'call_status' => 'cancel',
                'payment_method' => 'COD',
                'customer' => 'Ruwan Maduranga',
                'reseller' => null,
                'city_key' => 'Maharagama|Colombo',
                'courier' => 'SpeedX Courier',
                'courier_charge' => 300.00,
                'sales_note' => 'Cancelled by customer',
                'items' => [
                    ['sku' => 'NSE-BLK', 'quantity' => 1, 'selling_price' => 7900.00],
                ],
            ],
        ];

        $orderMap = [];

        foreach ($rows as $row) {
            $customer = $customers[$row['customer']] ?? null;
            $city = $cities[$row['city_key']] ?? null;
            $reseller = $row['reseller'] ? ($resellers[$row['reseller']] ?? null) : null;
            $courier = $couriers[$row['courier']] ?? null;
            $creator = $users['super_admin'];

            if (!$customer || !$city || !$courier) {
                continue;
            }

            $order = Order::query()->firstOrNew(['order_number' => $row['order_number']]);
            $order->order_number = $row['order_number'];
            $order->order_date = $row['order_date'];
            $order->order_type = $row['order_type'];
            $order->user_id = $creator->id;
            $order->reseller_id = $reseller?->id;
            $order->customer_id = $customer->id;
            $order->customer_name = $customer->name;
            $order->customer_phone = $customer->mobile;
            $order->customer_address = $customer->address;
            $order->city_id = $city->id;
            $order->status = $row['status'];
            $order->payment_method = $row['payment_method'];
            $order->payment_status = 'pending';
            $order->sales_note = $row['sales_note'];
            $order->courier_id = $courier->id;
            $order->courier_charge = $row['courier_charge'];
            $order->call_status = $row['call_status'];
            $order->customer_city = $city->city_name;
            $order->customer_district = $city->district;
            $order->customer_province = $city->province;
            $order->save();

            OrderItem::query()->where('order_id', $order->id)->delete();

            $subTotal = 0.0;
            $totalCost = 0.0;
            $totalCommission = 0.0;

            foreach ($row['items'] as $itemRow) {
                $variant = $variants[$itemRow['sku']] ?? ProductVariant::query()->where('sku', $itemRow['sku'])->first();
                if (!$variant) {
                    continue;
                }

                $qty = (int) $itemRow['quantity'];
                $unitPrice = (float) $itemRow['selling_price'];
                $basePrice = (float) ($variant->limit_price ?? 0);
                $lineTotal = $qty * $unitPrice;

                $subTotal += $lineTotal;
                $totalCost += $qty * $basePrice;
                if ($order->order_type === 'reseller') {
                    $totalCommission += ($unitPrice - $basePrice) * $qty;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name ?? $itemRow['sku'],
                    'sku' => $variant->sku,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'base_price' => $basePrice,
                    'cost_price' => $basePrice,
                    'total_price' => $lineTotal,
                    'subtotal' => $lineTotal,
                ]);
            }

            $order->total_amount = round($subTotal + (float) $row['courier_charge'], 2);
            $order->total_cost = round($totalCost, 2);
            $order->total_commission = round($totalCommission, 2);
            $order->save();

            OrderLog::query()->where('order_id', $order->id)->delete();
            OrderLog::create([
                'order_id' => $order->id,
                'user_id' => $creator->id,
                'action' => 'created',
                'description' => 'Seeded demo order.',
            ]);
            OrderLog::create([
                'order_id' => $order->id,
                'user_id' => $creator->id,
                'action' => 'status_updated',
                'description' => 'Seeded status set to ' . $order->status . '.',
            ]);

            $orderMap[$row['order_number']] = $order->fresh();
        }

        return $orderMap;
    }

    private function seedCourierPayments(
        array $users,
        array $couriers,
        array $bankAccounts,
        array $orders
    ): void {
        $rows = [
            [
                'reference_number' => 'CP-DEMO-0001',
                'courier' => 'SpeedX Courier',
                'amount' => 12600.00,
                'payment_date' => now()->subDay()->toDateString(),
                'payment_method' => 'Bank Transfer',
                'payment_note' => 'Weekly courier settlement',
                'bank_account' => 'Main Operations',
            ],
            [
                'reference_number' => 'CP-DEMO-0002',
                'courier' => 'Prompt Express',
                'amount' => 8200.00,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'Cash',
                'payment_note' => 'Partial payment',
                'bank_account' => 'Courier Float Wallet',
            ],
        ];

        $paymentMap = [];
        foreach ($rows as $row) {
            $courier = $couriers[$row['courier']] ?? null;
            $bankAccount = $bankAccounts[$row['bank_account']] ?? null;
            if (!$courier) {
                continue;
            }

            $payment = CourierPayment::updateOrCreate(
                ['reference_number' => $row['reference_number']],
                [
                    'courier_id' => $courier->id,
                    'user_id' => $users['super_admin']->id,
                    'amount' => $row['amount'],
                    'payment_date' => $row['payment_date'],
                    'payment_method' => $row['payment_method'],
                    'payment_note' => $row['payment_note'],
                    'bank_account_id' => $bankAccount?->id,
                ]
            );

            $paymentMap[$row['reference_number']] = $payment;
        }

        if (isset($orders['DEMO-ORD-0001'], $paymentMap['CP-DEMO-0001'])) {
            $order = $orders['DEMO-ORD-0001'];
            $order->courier_payment_id = $paymentMap['CP-DEMO-0001']->id;
            $order->save();
        }
    }

    private function seedResellerPayments(array $resellers): void
    {
        $rows = [
            [
                'reference_id' => 'RP-DEMO-0001',
                'reseller' => 'Glow Wholesale Traders',
                'amount' => 3500.00,
                'status' => 'paid',
                'payment_method' => 'bank',
                'payment_date' => now()->subDays(2),
            ],
            [
                'reference_id' => 'RP-DEMO-0002',
                'reseller' => 'Glow Wholesale Traders',
                'amount' => 1000.00,
                'status' => 'cancelled',
                'payment_method' => 'other',
                'payment_date' => now()->subDay(),
            ],
        ];

        foreach ($rows as $row) {
            $reseller = $resellers[$row['reseller']] ?? null;
            if (!$reseller || $reseller->reseller_type !== Reseller::TYPE_RESELLER) {
                continue;
            }

            ResellerPayment::updateOrCreate(
                ['reference_id' => $row['reference_id']],
                [
                    'reseller_id' => $reseller->id,
                    'amount' => $row['amount'],
                    'status' => $row['status'],
                    'payment_method' => $row['payment_method'],
                    'payment_date' => $row['payment_date'],
                ]
            );
        }
    }

    private function seedPurchases(array $suppliers, array $products): void
    {
        $rows = [
            [
                'purchase_number' => 'PUR-DEMO-0001',
                'supplier' => 'Nova Imports',
                'purchase_date' => now()->subDays(6)->toDateString(),
                'currency' => 'LKR',
                'discount_type' => 'fixed',
                'discount_value' => 500.00,
                'paid_amount' => 15000.00,
                'payment_method' => 'Bank Transfer',
                'payment_reference' => 'TRX-DEMO-001',
                'payment_account' => 'Main Operations',
                'payment_note' => 'Initial stock batch',
                'items' => [
                    ['product' => 'VoltLink USB-C Cable', 'quantity' => 20, 'purchase_price' => 900.00],
                    ['product' => 'Luma Desk Lamp', 'quantity' => 10, 'purchase_price' => 2500.00],
                ],
            ],
            [
                'purchase_number' => 'PUR-DEMO-0002',
                'supplier' => 'GreenLeaf Distributors',
                'purchase_date' => now()->subDays(4)->toDateString(),
                'currency' => 'LKR',
                'discount_type' => 'percentage',
                'discount_value' => 5.00,
                'paid_amount' => 10000.00,
                'payment_method' => 'Cash',
                'payment_reference' => 'CSH-DEMO-002',
                'payment_account' => 'Courier Float Wallet',
                'payment_note' => 'Grocery replenishment',
                'items' => [
                    ['product' => 'Premium Nadu Rice', 'quantity' => 30, 'purchase_price' => 220.00],
                    ['product' => 'FreshDay Orange Juice', 'quantity' => 25, 'purchase_price' => 560.00],
                ],
            ],
        ];

        foreach ($rows as $row) {
            $supplier = $suppliers[$row['supplier']] ?? null;
            if (!$supplier) {
                continue;
            }

            $purchase = Purchase::query()->firstOrNew(['purchase_number' => $row['purchase_number']]);
            $purchase->purchase_number = $row['purchase_number'];
            $purchase->supplier_id = $supplier->id;
            $purchase->purchase_date = $row['purchase_date'];
            $purchase->currency = $row['currency'];
            $purchase->discount_type = $row['discount_type'];
            $purchase->discount_value = $row['discount_value'];
            $purchase->payment_method = $row['payment_method'];
            $purchase->payment_reference = $row['payment_reference'];
            $purchase->payment_account = $row['payment_account'];
            $purchase->payment_note = $row['payment_note'];
            $purchase->save();

            PurchaseItem::query()->where('purchase_id', $purchase->id)->delete();

            $subTotal = 0.0;
            foreach ($row['items'] as $itemRow) {
                $product = $products[$itemRow['product']] ?? null;
                if (!$product) {
                    continue;
                }

                $lineTotal = (float) $itemRow['purchase_price'] * (int) $itemRow['quantity'];
                $subTotal += $lineTotal;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $itemRow['quantity'],
                    'purchase_price' => $itemRow['purchase_price'],
                    'total' => $lineTotal,
                ]);
            }

            $discountAmount = $row['discount_type'] === 'percentage'
                ? ($subTotal * ((float) $row['discount_value'] / 100))
                : (float) $row['discount_value'];

            $netTotal = max(0, $subTotal - $discountAmount);
            $paid = min((float) $row['paid_amount'], $netTotal);

            $purchase->sub_total = round($subTotal, 2);
            $purchase->discount_amount = round($discountAmount, 2);
            $purchase->net_total = round($netTotal, 2);
            $purchase->paid_amount = round($paid, 2);
            $purchase->payments_data = [
                [
                    'method' => $row['payment_method'],
                    'amount' => round($paid, 2),
                    'reference' => $row['payment_reference'],
                    'account' => $row['payment_account'],
                    'note' => $row['payment_note'],
                ],
            ];
            $purchase->save();

            $supplier->due_amount = round(
                (float) Purchase::query()->where('supplier_id', $supplier->id)->sum('net_total')
                - (float) Purchase::query()->where('supplier_id', $supplier->id)->sum('paid_amount'),
                2
            );
            $supplier->save();
        }
    }

    private function syncResellerDueAmounts(array $resellers): void
    {
        foreach ($resellers as $reseller) {
            $orderTotal = (float) Order::query()
                ->where('reseller_id', $reseller->id)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');

            $paidTotal = (float) ResellerPayment::query()
                ->where('reseller_id', $reseller->id)
                ->where('status', '!=', 'cancelled')
                ->sum('amount');

            $reseller->due_amount = round($orderTotal - $paidTotal, 2);
            $reseller->save();
        }
    }

    private function seedAttributes(): void
    {
        $attributes = [
            'Color' => ['Black', 'White', 'Silver'],
            'Size' => ['Small', 'Medium', 'Large'],
        ];

        foreach ($attributes as $name => $values) {
            $attribute = ProductAttribute::firstOrCreate(['name' => $name]);

            foreach ($values as $value) {
                AttributeValue::firstOrCreate([
                    'attribute_id' => $attribute->id,
                    'value' => $value,
                ]);
            }
        }
    }
}
