<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homecare_inform_concerns', function (Blueprint $table) {
            $table->id();
            $table->string('noreg', 20);
            $table->string('norm', 20);
            $table->string('document_type', 20);
            $table->string('applicant_name', 150)->nullable();
            $table->string('applicant_age_gender', 100)->nullable();
            $table->text('applicant_address')->nullable();
            $table->string('applicant_phone', 50)->nullable();
            $table->string('patient_relationship', 50)->nullable();
            $table->string('patient_name', 150)->nullable();
            $table->string('patient_age_gender', 100)->nullable();
            $table->text('patient_address')->nullable();
            $table->text('service_type')->nullable();
            $table->boolean('homecare_24_hours')->default(false);
            $table->string('nurse_visit_frequency', 100)->nullable();
            $table->string('doctor_visit_frequency', 100)->nullable();
            $table->string('other_staff', 150)->nullable();
            $table->string('other_visit_frequency', 100)->nullable();
            $table->longText('nursing_actions')->nullable();
            $table->string('witness_name', 150)->nullable();
            $table->string('signer_name', 150)->nullable();
            $table->string('ttd_signer')->nullable();
            $table->string('ttd_witness')->nullable();
            $table->date('signed_at')->nullable();
            $table->timestamps();

            $table->unique(['noreg', 'document_type'], 'homecare_inform_concerns_noreg_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homecare_inform_concerns');
    }
};
