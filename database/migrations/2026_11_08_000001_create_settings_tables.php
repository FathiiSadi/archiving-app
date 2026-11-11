<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 9); // e.g. 2025/2026
            $table->string('term', 16);         // App\Enums\Term
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedTinyInteger('tajweed_target_rate')->default(70); // % (BR-11)
            $table->boolean('is_current')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['academic_year', 'term']);
        });

        // Approved budget and estimated cap per team per semester (amounts in JOD).
        Schema::create('semester_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('team', 32);
            $table->decimal('approved_budget', 12, 3)->default(0);
            $table->decimal('budget_cap', 12, 3)->default(0);
            $table->timestamps();

            $table->unique(['semester_id', 'team']);
        });

        // List of approved activities per semester (Activities + Internal Affairs teams).
        Schema::create('approved_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('team', 32)->index();
            $table->string('name');
            $table->decimal('estimated_budget', 12, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['semester_id', 'team', 'name']);
        });

        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_types');
        Schema::dropIfExists('approved_activities');
        Schema::dropIfExists('semester_budgets');
        Schema::dropIfExists('semesters');
    }
};
