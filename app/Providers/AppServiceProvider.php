<?php

namespace App\Providers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\City;
use App\Models\Courier;
use App\Models\CourierPayment;
use App\Models\CourierWaybill;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\InventoryUnitEvent;
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
use App\Models\StoreRack;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Observers\AuditModelObserver;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ($this->auditedModels() as $model) {
            $model::observe(AuditModelObserver::class);
        }
    }

    /**
     * @return array<int, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private function auditedModels(): array
    {
        return [
            Attribute::class,
            AttributeValue::class,
            BankAccount::class,
            Category::class,
            City::class,
            Courier::class,
            CourierPayment::class,
            CourierWaybill::class,
            Customer::class,
            InventoryUnit::class,
            InventoryUnitEvent::class,
            Order::class,
            OrderItem::class,
            OrderLog::class,
            Product::class,
            ProductVariant::class,
            Purchase::class,
            PurchaseItem::class,
            Reseller::class,
            ResellerPayment::class,
            ResellerTarget::class,
            Role::class,
            Permission::class,
            StoreRack::class,
            SubCategory::class,
            Supplier::class,
            Unit::class,
            User::class,
        ];
    }
}
