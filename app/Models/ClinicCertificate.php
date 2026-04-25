<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicCertificate extends Model
{
    protected $fillable = [
        'clinic_id',
        'certificate_type',
        'file_path',
        'password',
        'valid_until',
        'signer_name',
    ];

    protected $casts = [
        'valid_until' => 'date',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
