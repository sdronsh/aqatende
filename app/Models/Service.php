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
        'shared_service',
        'is_package',
    ];

    protected $casts = [
        'duration_minutes' => 'int',
        'price_cents' => 'int',
        'active' => 'bool',
        'shared_service' => 'bool',
        'is_package' => 'bool',
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

    public function packageItems()
    {
        return $this->belongsToMany(Service::class, 'service_package_items', 'package_service_id', 'component_service_id')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function usedInPackages()
    {
        return $this->belongsToMany(Service::class, 'service_package_items', 'component_service_id', 'package_service_id')
            ->withPivot('position')
            ->withTimestamps();
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
