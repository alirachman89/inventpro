<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('document_type')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('approval_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('name');
            $table->string('approver_role');
            $table->string('mode', 20)->default('any');
            $table->timestamps();

            $table->unique(['workflow_id', 'step_order']);
        });

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
            $table->string('document_type')->index();
            $table->uuid('document_id')->index();
            $table->string('document_label');
            $table->foreignUuid('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('current_step_order')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('approval_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->foreignUuid('step_id')->nullable()->constrained('approval_steps')->nullOnDelete();
            $table->unsignedInteger('step_order');
            $table->foreignUuid('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 20);
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50)->index();
            $table->string('title');
            $table->text('message');
            $table->string('link')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('approval_demos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_demos');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('approval_actions');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('approval_workflows');
    }
};
