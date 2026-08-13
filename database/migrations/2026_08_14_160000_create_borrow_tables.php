<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->string('status', 32)->default('draft');
            $table->foreignUuid('borrower_user_id')->constrained('users');
            $table->foreignUuid('client_id')->constrained('clients');
            $table->date('borrow_date');
            $table->date('due_date');
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
            $table->index(['borrower_user_id', 'client_id']);
        });

        Schema::create('borrow_request_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('borrow_request_id')->constrained('borrow_requests')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('items');
            $table->foreignUuid('asset_unit_id')->nullable()->constrained('asset_units')->nullOnDelete();
            $table->decimal('qty', 15, 3)->default(1);
            $table->decimal('qty_checked_out', 15, 3)->default(0);
            $table->decimal('qty_returned', 15, 3)->default(0);
            $table->foreignUuid('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('from_rack_id')->nullable()->constrained('racks')->nullOnDelete();
            $table->string('condition_on_return', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_request_lines');
        Schema::dropIfExists('borrow_requests');
    }
};
