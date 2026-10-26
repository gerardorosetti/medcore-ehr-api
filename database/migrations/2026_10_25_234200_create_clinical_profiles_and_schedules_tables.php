<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('specialty', 100)->index();
            $table->string('license_number', 50)->unique();
            $table->string('phone', 30)->nullable();
            $table->text('bio')->nullable();
            $table->unsignedSmallInteger('consultation_duration_minutes')->default(30);
            $table->boolean('is_accepting_patients')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('patient_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->date('date_of_birth');
            $table->string('gender', 30)->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->string('emergency_contact_name', 120)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('doctor_profile_id')->constrained('doctor_profiles')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['doctor_profile_id', 'day_of_week', 'start_time'], 'doc_schedule_unique_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
        Schema::dropIfExists('patient_profiles');
        Schema::dropIfExists('doctor_profiles');
    }
};
