<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $fillable = [
        'clinic_id',
        'unit_id',
        'professional_id',
        'patient_id',
        'service_id',
        'status',
        'channel',
        'scheduled_at',
        'ends_at',
        'started_at',
        'finished_at',
        'duration_minutes',
        'is_first_visit',
        'notes',
        'recurrence_group_id',
        'recurrence_index',
        'price_cents',
        'commission_amount_cents',
        'salon_amount_cents',
        'payment_status',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'ends_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_minutes' => 'int',
        'recurrence_index' => 'int',
        'is_first_visit' => 'bool',
        'price_cents' => 'int',
        'commission_amount_cents' => 'int',
        'salon_amount_cents' => 'int',
        'cancelled_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot([
                'professional_id',
                'duration_minutes',
                'price_cents',
                'scheduled_at',
                'ends_at',
                'status',
                'commission_amount_cents',
                'position',
            ])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function serviceNames(): string
    {
        $services = $this->relationLoaded('services') ? $this->services : $this->services()->get();

        if ($services->isNotEmpty()) {
            return $services->pluck('name')->join(' + ');
        }

        return $this->service?->name ?? 'Atendimento';
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function receivable(): HasOne
    {
        return $this->hasOne(AccountReceivable::class, 'appointment_id');
    }

    public function calculateCommissionCents(): int
    {
        if (! $this->professional_id || ! $this->price_cents) {
            return 0;
        }

        $professional = $this->professional ?? Professional::find($this->professional_id);
        if (! $professional) {
            return 0;
        }

        $appointmentServices = $this->services()->get();
        if ($appointmentServices->isNotEmpty()) {
            return $appointmentServices->sum(function (Service $service) use ($professional) {
                $priceCents = (int) ($service->pivot->price_cents ?? $service->price_cents ?? 0);
                if ($priceCents <= 0) {
                    return 0;
                }

                $itemProfessional = $service->pivot->professional_id
                    ? Professional::find($service->pivot->professional_id)
                    : $professional;

                if (! $itemProfessional) {
                    return 0;
                }

                $pivot = $itemProfessional->services()
                    ->where('services.id', $service->id)
                    ->wherePivot('active', true)
                    ->first()?->pivot;

                $type = $pivot?->commission_type ?: $itemProfessional->commission_type;
                $value = $pivot?->commission_value ?? $itemProfessional->commission_value;

                if (! $type || $value === null) {
                    return 0;
                }

                if ($type === 'percentage') {
                    return (int) round($priceCents * ((float) $value / 100));
                }

                return min((int) round(((float) $value) * 100), $priceCents);
            });
        }

        $pivot = $professional->services()
            ->where('services.id', $this->service_id)
            ->wherePivot('active', true)
            ->first()?->pivot;

        $type = $pivot?->commission_type ?: $professional->commission_type;
        $value = $pivot?->commission_value ?? $professional->commission_value;

        if (! $type || $value === null) {
            return 0;
        }

        if ($type === 'percentage') {
            return (int) round($this->price_cents * ((float) $value / 100));
        }

        return min((int) round(((float) $value) * 100), $this->price_cents);
    }
}
