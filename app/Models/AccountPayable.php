<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountPayable extends Model
{
    protected $table = 'contas_pagar';

    protected $fillable = [
        'clinic_id',
        'unit_id',
        'professional_id',
        'appointment_id',
        'categoria_financeira_id',
        'fornecedor',
        'descricao',
        'valor_cents',
        'data_emissao',
        'data_vencimento',
        'pago_em',
        'status',
        'forma_pagamento',
        'centro_custo',
        'observacoes',
        'origem',
    ];

    protected $casts = [
        'data_emissao' => 'date',
        'data_vencimento' => 'date',
        'pago_em' => 'datetime',
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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'categoria_financeira_id');
    }
}
