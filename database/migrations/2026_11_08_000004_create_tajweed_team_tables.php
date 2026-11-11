<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // BR-09: instructors & supervisors
        Schema::create('tajweed_staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('gender', 8)->nullable();
            $table->text('phone')->nullable(); // encrypted at rest
            $table->string('role', 16)->default('instructor');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('tajweed_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('tajweed_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('tajweed_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('instructor_id')->constrained('tajweed_staff')->restrictOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('tajweed_staff')->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->unsignedSmallInteger('planned_lessons')->default(0);
            $table->string('schedule')->nullable();
            $table->string('archive_status', 16)->default('not_archived')->index();
            $table->string('drive_folder_url', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // BR-09: lectures and practical lessons
        Schema::create('tajweed_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tajweed_section_id')->constrained()->cascadeOnDelete();
            $table->date('held_on')->index();
            $table->string('type', 16)->default('theoretical');
            $table->string('topic')->nullable();
            $table->unsignedSmallInteger('enrolled_count')->default(0);
            $table->unsignedSmallInteger('present_count')->default(0);
            $table->unsignedTinyInteger('interaction_rating')->nullable(); // 1–5
            $table->unsignedTinyInteger('correction_rating')->nullable();  // 1–5, practical recitation correction
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('tajweed_students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('gender', 8)->nullable();
            $table->text('phone')->nullable(); // encrypted at rest
            $table->string('student_number', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // BR-10: scores, final exam and eligibility per student per section
        Schema::create('tajweed_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tajweed_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tajweed_student_id')->constrained()->cascadeOnDelete();
            $table->decimal('recitation_score', 5, 2)->nullable();  // theoretical recitation, /100
            $table->decimal('practical_score', 5, 2)->nullable();   // practical mastery, /100
            $table->decimal('final_exam_score', 5, 2)->nullable();  // /100
            $table->string('eligibility', 16)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tajweed_section_id', 'tajweed_student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tajweed_enrollments');
        Schema::dropIfExists('tajweed_students');
        Schema::dropIfExists('tajweed_lessons');
        Schema::dropIfExists('tajweed_sections');
        Schema::dropIfExists('tajweed_levels');
        Schema::dropIfExists('tajweed_staff');
    }
};
