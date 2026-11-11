<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // BR-13
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 16)->default('vendor');
            $table->string('service')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('phone')->nullable(); // encrypted at rest
            $table->decimal('rating', 3, 1)->nullable(); // out of 10
            $table->string('invoice_url', 2048)->nullable();
            $table->boolean('suitable_for_future')->nullable(); // true = whitelist, false = blacklist
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // BR-12
        Schema::create('internal_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('approved_activity_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('held_on');
            $table->decimal('cost_per_person', 10, 3)->default(0);
            $table->unsignedInteger('participants')->default(0);
            $table->decimal('revenue', 12, 3)->default(0);
            $table->decimal('actual_cost', 12, 3)->default(0);
            $table->decimal('net_result', 12, 3)->default(0); // revenue − actual cost (computed)
            $table->unsignedTinyInteger('final_rating')->nullable(); // final KPI, 1–5
            $table->string('invoice_url', 2048)->nullable();
            $table->string('archive_status', 16)->default('not_archived')->index();
            $table->string('drive_folder_url', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_activities');
        Schema::dropIfExists('vendors');
    }
};
