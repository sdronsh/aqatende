<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicPartner extends Model
{
    protected $fillable = [
        'clinic_id',
        'name',
        'cpf',
        'email',
        'phone',
        'role',
        'share_percent',
        'repasse',
    ];

    protected function casts(): array
    {
        return [
            'repasse' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
