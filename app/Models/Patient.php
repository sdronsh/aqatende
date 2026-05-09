<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'social_name',
        'cpf',
        'rg',
        'rg_issuer',
        'rg_state',
        'cns',
        'passport',
        'phone',
        'cellphone',
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
        'insurance_plan',
        'has_insurance',
        'insurance_name',
        'insurance_card_number',
        'insurance_plan_name',
        'insurance_card_valid_until',
        'insurance_accommodation',
        'insurance_holder',
        'birthdate',
        'gender',
        'gender_identity',
        'marital_status',
        'nationality',
        'birthplace',
        'photo_path',
        'status',
        'address_zip',
        'address_street',
        'address_number',
        'address_complement',
        'address_district',
        'address_city',
        'address_state',
        'address_country',
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
    ];

    protected $casts = [
        'birthdate' => 'date',
        'insurance_card_valid_until' => 'date',
        'last_menstrual_period' => 'date',
        'whatsapp' => 'bool',
        'has_insurance' => 'bool',
        'insurance_holder' => 'bool',
        'controlled_medication' => 'bool',
        'suicide_history' => 'bool',
        'pregnant' => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function bookingLinks(): HasMany
    {
        return $this->hasMany(PatientBookingLink::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }
}
