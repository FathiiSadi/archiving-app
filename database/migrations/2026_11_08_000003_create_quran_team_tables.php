<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('gender', 8)->nullable();
            $table->text('phone')->nullable(); // encrypted at rest
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // BR-05
        Schema::create('quran_circles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('quran_teacher_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->default(0);
            $table->decimal('teacher_rating', 3, 1)->nullable(); // out of 10
            $table->string('schedule')->nullable();
            $table->string('location')->nullable();
            $table->string('archive_status', 16)->default('not_archived')->index();
            $table->string('drive_folder_url', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // BR-06
        Schema::create('recitation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quran_circle_id')->constrained()->cascadeOnDelete();
            $table->date('held_on')->index();
            $table->unsignedSmallInteger('duration_minutes')->default(0);
            $table->string('mode', 16)->default('in_person');
            $table->string('teacher_attendance', 16)->default('held');
            $table->boolean('is_compensation')->default(false);
            $table->unsignedSmallInteger('total_students')->default(0);
            $table->unsignedSmallInteger('present_students')->default(0);
            $table->decimal('new_pages', 7, 2)->default(0);
            $table->decimal('revision_pages', 7, 2)->default(0);
            $table->decimal('pages_recited', 8, 2)->default(0); // = new + revision (computed)
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // BR-07
        Schema::create('circle_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quran_circle_id')->constrained()->cascadeOnDelete();
            $table->date('evaluated_on');
            $table->unsignedTinyInteger('memorization_accuracy');
            $table->unsignedTinyInteger('interaction');
            $table->unsignedTinyInteger('punctuality');
            $table->unsignedTinyInteger('tajweed_rules');
            $table->unsignedTinyInteger('plan_achievement');
            $table->decimal('average', 3, 2)->default(0); // computed
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circle_evaluations');
        Schema::dropIfExists('recitation_sessions');
        Schema::dropIfExists('quran_circles');
        Schema::dropIfExists('quran_teachers');
    }
};
