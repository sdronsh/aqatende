<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'appointment_id',
        'method',
        'status',
        'amount_cents',
        'fee_cents',
        'paid_at',
        'external_id',
    ];

    protected $casts = [
        'amount_cents' => 'int',
        'fee_cents' => 'int',
        'paid_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
