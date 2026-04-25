<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'clinic_id',
        'appointment_id',
        'type',
        'amount_cents',
        'payment_method',
        'description',
        'paid_at',
    ];

    protected $casts = [
        'amount_cents' => 'int',
        'paid_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
