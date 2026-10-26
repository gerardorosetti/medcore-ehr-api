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
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_profile_id')->constrained('patient_profiles')->cascadeOnDelete();
            $table->foreignUuid('doctor_profile_id')->constrained('doctor_profiles')->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('pending')->index();
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['doctor_profile_id', 'scheduled_at', 'status'], 'idx_doctor_schedule_status');
            $table->index(['patient_profile_id', 'scheduled_at'], 'idx_patient_schedule');
        });

        Schema::create('medical_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->unique()->constrained('appointments')->cascadeOnDelete();
            $table->foreignUuid('patient_profile_id')->constrained('patient_profiles')->cascadeOnDelete();
            $table->foreignUuid('doctor_profile_id')->constrained('doctor_profiles')->cascadeOnDelete();
            $table->text('diagnosis');
            $table->text('clinical_notes');
            $table->text('treatment_plan')->nullable();
            $table->json('vital_signs')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('medical_record_id')->constrained('medical_records')->cascadeOnDelete();
            $table->string('medication_name', 150);
            $table->string('dosage', 80);
            $table->string('frequency', 100);
            $table->unsignedSmallInteger('duration_days');
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80)->index();
            $table->string('auditable_type', 120);
            $table->uuid('auditable_id');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id'], 'idx_auditable_target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('appointments');
    }
};
