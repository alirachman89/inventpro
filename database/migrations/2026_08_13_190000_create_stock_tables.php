<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('name');
            $table->string('item_type', 32); // consumable|asset
            $table->boolean('is_serialized')->default(false);
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignUuid('uom_id')->constrained('units');
            $table->text('description')->nullable();
            $table->decimal('min_stock', 15, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('item_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignUuid('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignUuid('rack_id')->constrained('racks')->cascadeOnDelete();
            $table->decimal('qty_on_hand', 15, 3)->default(0);
            $table->decimal('qty_reserved', 15, 3)->default(0);
            $table->string('condition', 32)->default('good'); // good|damaged|quarantine|expired
            $table->timestamps();

            $table->unique(['item_id', 'location_id', 'rack_id', 'condition'], 'item_stocks_unique_position');
        });

        Schema::create('asset_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('asset_tag')->unique();
            $table->string('serial_number')->nullable();
            $table->string('status', 32)->default('available');
            $table->string('condition', 32)->default('good');
            $table->foreignUuid('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('rack_id')->nullable()->constrained('racks')->nullOnDelete();
            $table->foreignUuid('current_holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('current_client_id')->nullable(); // FK di Phase 6
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asset_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('asset_unit_id')->constrained('asset_units')->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignUuid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignUuid('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignUuid('rack_id')->constrained('racks')->cascadeOnDelete();
            $table->string('movement_type', 32); // adjust_in|adjust_out|opening|seed
            $table->decimal('qty_delta', 15, 3);
            $table->decimal('qty_before', 15, 3);
            $table->decimal('qty_after', 15, 3);
            $table->string('condition', 32)->default('good');
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
        Schema::dropIfExists('asset_status_histories');
        Schema::dropIfExists('asset_units');
        Schema::dropIfExists('item_stocks');
        Schema::dropIfExists('items');
    }
};
