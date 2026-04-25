<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicContact extends Model
{
    protected $fillable = [
        'clinic_id',
        'address_line1',
        'number',
        'complement',
        'district',
        'zip',
        'city',
        'state',
        'phone',
        'whatsapp',
        'email',
        'website',
        'admin_responsible',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
