<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'clinic_id',
        'unit_id',
        'name',
        'description',
        'duration_minutes',
        'modality',
        'price_cents',
        'active',
    ];

    protected $casts = [
        'duration_minutes' => 'int',
        'price_cents' => 'int',
        'active' => 'bool',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function professionals()
    {
        return $this->belongsToMany(Professional::class)
            ->withPivot([
                'custom_price_cents',
                'duration_minutes',
                'commission_type',
                'commission_value',
                'active',
            ])
            ->withTimestamps();
    }
}
