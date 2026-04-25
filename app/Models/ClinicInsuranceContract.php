<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicInsuranceContract extends Model
{
    protected $fillable = [
        'clinic_id',
        'plan_name',
        'credential_code',
        'contract_type',
        'contract_file_path',
        'table_type',
        'glosa_percent',
        'submission_type',
    ];

    protected $casts = [
        'glosa_percent' => 'decimal:2',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
