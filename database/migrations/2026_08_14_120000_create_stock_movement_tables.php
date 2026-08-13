<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->string('type', 16); // in|out|transfer
            $table->string('reason', 32);
            $table->date('movement_date');
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_movement_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_movement_id')->constrained('stock_movements')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('items');
            $table->decimal('qty', 15, 3);
            $table->string('condition', 32)->default('good');
            $table->foreignUuid('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('from_rack_id')->nullable()->constrained('racks')->nullOnDelete();
            $table->foreignUuid('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('to_rack_id')->nullable()->constrained('racks')->nullOnDelete();
            $table->foreignUuid('asset_unit_id')->nullable()->constrained('asset_units')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movement_lines');
        Schema::dropIfExists('stock_movements');
    }
};
