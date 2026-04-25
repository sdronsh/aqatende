<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlowEntry extends Model
{
    protected $table = 'fluxo_caixa';

    protected $fillable = [
        'clinic_id',
        'unit_id',
        'professional_id',
        'conta_bancaria_id',
        'categoria_financeira_id',
        'user_id',
        'tipo',
        'origem',
        'origem_id',
        'descricao',
        'valor_cents',
        'data_movimento',
        'forma_pagamento',
        'observacoes',
    ];

    protected $casts = [
        'data_movimento' => 'datetime',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'conta_bancaria_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'categoria_financeira_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
