<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->foreignUuid('location_id')->constrained('locations');
            $table->string('status', 32)->default('draft');
            $table->date('opname_date');
            $table->foreignUuid('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opname_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('items');
            $table->foreignUuid('rack_id')->constrained('racks');
            $table->string('condition', 32)->default('good');
            $table->decimal('qty_system', 15, 3);
            $table->decimal('qty_counted', 15, 3)->nullable();
            $table->decimal('qty_variance', 15, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['stock_opname_id', 'item_id', 'rack_id', 'condition'], 'stock_opname_lines_unique_pos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_lines');
        Schema::dropIfExists('stock_opnames');
    }
};
