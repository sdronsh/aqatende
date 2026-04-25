<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('social_name')->nullable()->after('full_name');
            $table->string('gender_identity')->nullable()->after('gender');
            $table->string('marital_status')->nullable()->after('gender_identity');
            $table->string('nationality')->nullable()->after('marital_status');
            $table->string('birthplace')->nullable()->after('nationality');
            $table->string('photo_path')->nullable()->after('birthplace');
            $table->string('status')->default('ativo')->after('photo_path');

            $table->string('rg')->nullable()->after('cpf');
            $table->string('rg_issuer')->nullable()->after('rg');
            $table->string('rg_state', 2)->nullable()->after('rg_issuer');
            $table->string('cns')->nullable()->after('rg_state');
            $table->string('passport')->nullable()->after('cns');

            $table->string('address_zip', 12)->nullable()->after('passport');
            $table->string('address_street')->nullable()->after('address_zip');
            $table->string('address_number')->nullable()->after('address_street');
            $table->string('address_complement')->nullable()->after('address_number');
            $table->string('address_district')->nullable()->after('address_complement');
            $table->string('address_city')->nullable()->after('address_district');
            $table->string('address_state', 2)->nullable()->after('address_city');
            $table->string('address_country', 2)->nullable()->after('address_state');

            $table->boolean('whatsapp')->default(false)->after('cellphone');
            $table->string('emergency_contact_name')->nullable()->after('whatsapp');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_phone');

            $table->string('mother_name')->nullable()->after('emergency_contact_relationship');
            $table->string('father_name')->nullable()->after('mother_name');
            $table->string('legal_guardian_name')->nullable()->after('father_name');
            $table->string('legal_guardian_cpf')->nullable()->after('legal_guardian_name');
            $table->string('legal_guardian_phone')->nullable()->after('legal_guardian_cpf');
            $table->string('guardian_relationship')->nullable()->after('legal_guardian_phone');

            $table->boolean('has_insurance')->default(false)->after('insurance_plan');
            $table->string('insurance_name')->nullable()->after('has_insurance');
            $table->string('insurance_card_number')->nullable()->after('insurance_name');
            $table->string('insurance_plan_name')->nullable()->after('insurance_card_number');
            $table->date('insurance_card_valid_until')->nullable()->after('insurance_plan_name');
            $table->string('insurance_accommodation')->nullable()->after('insurance_card_valid_until');
            $table->boolean('insurance_holder')->default(false)->after('insurance_accommodation');

            $table->string('blood_type')->nullable()->after('insurance_holder');
            $table->decimal('weight_kg', 5, 2)->nullable()->after('blood_type');
            $table->decimal('height_cm', 5, 2)->nullable()->after('weight_kg');
            $table->string('blood_pressure')->nullable()->after('height_cm');
            $table->string('heart_rate')->nullable()->after('blood_pressure');
            $table->string('respiratory_rate')->nullable()->after('heart_rate');
            $table->string('temperature')->nullable()->after('respiratory_rate');

            $table->text('preexisting_conditions')->nullable()->after('temperature');
            $table->text('chronic_conditions')->nullable()->after('preexisting_conditions');
            $table->text('allergies')->nullable()->after('chronic_conditions');
            $table->text('current_medications')->nullable()->after('allergies');
            $table->text('previous_surgeries')->nullable()->after('current_medications');
            $table->text('previous_hospitalizations')->nullable()->after('previous_surgeries');
            $table->text('family_history')->nullable()->after('previous_hospitalizations');
            $table->text('clinical_notes')->nullable()->after('family_history');

            $table->string('smoker')->nullable()->after('clinical_notes');
            $table->string('alcohol_use')->nullable()->after('smoker');
            $table->string('physical_activity')->nullable()->after('alcohol_use');
            $table->string('diet')->nullable()->after('physical_activity');
            $table->string('drug_use')->nullable()->after('diet');
            $table->string('sleep_quality')->nullable()->after('drug_use');

            $table->string('psych_diagnosis')->nullable()->after('sleep_quality');
            $table->string('psych_followup')->nullable()->after('psych_diagnosis');
            $table->boolean('controlled_medication')->default(false)->after('psych_followup');
            $table->boolean('suicide_history')->default(false)->after('controlled_medication');

            $table->boolean('pregnant')->default(false)->after('suicide_history');
            $table->string('gestational_age')->nullable()->after('pregnant');
            $table->string('pregnancies_count')->nullable()->after('gestational_age');
            $table->string('births_count')->nullable()->after('pregnancies_count');
            $table->string('abortions_count')->nullable()->after('births_count');
            $table->date('last_menstrual_period')->nullable()->after('abortions_count');
            $table->string('contraceptive_use')->nullable()->after('last_menstrual_period');

            $table->string('medical_record_number')->nullable()->after('contraceptive_use');
            $table->string('service_unit')->nullable()->after('medical_record_number');
            $table->string('responsible_doctor')->nullable()->after('service_unit');
            $table->string('specialty')->nullable()->after('responsible_doctor');
            $table->string('created_by_name')->nullable()->after('specialty');
            $table->text('admin_notes')->nullable()->after('created_by_name');

            $table->text('attachments_documents')->nullable()->after('admin_notes');
            $table->text('attachments_exams')->nullable()->after('attachments_documents');
            $table->text('attachments_prescriptions')->nullable()->after('attachments_exams');
            $table->text('attachments_reports')->nullable()->after('attachments_prescriptions');
            $table->text('attachments_certificates')->nullable()->after('attachments_reports');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'social_name',
                'gender_identity',
                'marital_status',
                'nationality',
                'birthplace',
                'photo_path',
                'status',
                'rg',
                'rg_issuer',
                'rg_state',
                'cns',
                'passport',
                'address_zip',
                'address_street',
                'address_number',
                'address_complement',
                'address_district',
                'address_city',
                'address_state',
                'address_country',
                'whatsapp',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relationship',
                'mother_name',
                'father_name',
                'legal_guardian_name',
                'legal_guardian_cpf',
                'legal_guardian_phone',
                'guardian_relationship',
                'has_insurance',
                'insurance_name',
                'insurance_card_number',
                'insurance_plan_name',
                'insurance_card_valid_until',
                'insurance_accommodation',
                'insurance_holder',
                'blood_type',
                'weight_kg',
                'height_cm',
                'blood_pressure',
                'heart_rate',
                'respiratory_rate',
                'temperature',
                'preexisting_conditions',
                'chronic_conditions',
                'allergies',
                'current_medications',
                'previous_surgeries',
                'previous_hospitalizations',
                'family_history',
                'clinical_notes',
                'smoker',
                'alcohol_use',
                'physical_activity',
                'diet',
                'drug_use',
                'sleep_quality',
                'psych_diagnosis',
                'psych_followup',
                'controlled_medication',
                'suicide_history',
                'pregnant',
                'gestational_age',
                'pregnancies_count',
                'births_count',
                'abortions_count',
                'last_menstrual_period',
                'contraceptive_use',
                'medical_record_number',
                'service_unit',
                'responsible_doctor',
                'specialty',
                'created_by_name',
                'admin_notes',
                'attachments_documents',
                'attachments_exams',
                'attachments_prescriptions',
                'attachments_reports',
                'attachments_certificates',
            ]);
        });
    }
};
