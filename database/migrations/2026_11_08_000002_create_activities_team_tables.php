<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // BR-01
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('approved_activity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('activity_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->date('held_on')->nullable();
            $table->string('location')->nullable();
            $table->string('status', 16)->default('planned')->index();
            $table->unsignedInteger('expected_attendance')->default(0);
            $table->unsignedInteger('actual_attendance')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // 1–5
            $table->string('archive_status', 16)->default('not_archived')->index();
            $table->string('drive_folder_url', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // BR-02
        Schema::create('activity_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->date('purchased_on')->nullable();
            $table->string('supplier');
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('total_cost', 12, 3); // JOD
            $table->boolean('within_budget')->default(true);
            $table->unsignedTinyInteger('supplier_rating')->nullable(); // 1–5
            $table->string('invoice_url', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // BR-03
        Schema::create('activity_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('gender', 8)->nullable();
            $table->text('phone')->nullable(); // encrypted at rest
            $table->string('role', 16)->default('guest');
            $table->string('affiliation')->nullable();
            $table->string('topic')->nullable();
            $table->string('invitation_status', 32)->default('invited')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_guests');
        Schema::dropIfExists('activity_purchases');
        Schema::dropIfExists('activities');
    }
};
