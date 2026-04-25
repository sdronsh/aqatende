<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicResponsible extends Model
{
    protected $fillable = [
        'clinic_id',
        'type',
        'name',
        'cpf',
        'council_type',
        'council_number',
        'specialty',
        'certificate_path',
        'email',
        'phone',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
