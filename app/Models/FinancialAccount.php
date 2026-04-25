<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialAccount extends Model
{
    protected $table = 'contas_bancarias';

    protected $fillable = [
        'clinic_id',
        'unit_id',
        'name',
        'type',
        'bank_name',
        'agency',
        'account_number',
        'pix_key',
        'initial_balance_cents',
        'active',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
