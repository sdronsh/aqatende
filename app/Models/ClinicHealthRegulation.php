<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicHealthRegulation extends Model
{
    protected $fillable = [
        'clinic_id',
        'anvisa',
        'cnes',
        'sanitary_permit',
        'permit_issued_at',
        'permit_valid_until',
        'tech_responsible_name',
        'tech_responsible_council',
        'tech_responsible_number',
        'specialties',
        'ans_enabled',
        'ans_registration',
        'accreditation_type',
        'tables_used',
        'insurance_plans',
    ];

    protected $casts = [
        'permit_issued_at' => 'date',
        'permit_valid_until' => 'date',
        'ans_enabled' => 'bool',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
