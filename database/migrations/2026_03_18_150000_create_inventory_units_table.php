<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit_code')->nullable()->unique();
            $table->string('status', 32)->index();
            $table->string('sku_snapshot')->nullable();
            $table->string('product_name_snapshot')->nullable();
            $table->string('variant_label_snapshot')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'status']);
            $table->index(['purchase_id', 'status']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_units');
    }
};
