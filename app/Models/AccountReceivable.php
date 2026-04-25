<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountReceivable extends Model
{
    protected $table = 'contas_receber';

    protected $fillable = [
        'clinic_id',
        'unit_id',
        'professional_id',
        'patient_id',
        'appointment_id',
        'categoria_financeira_id',
        'descricao',
        'valor_total_cents',
        'numero_parcelas',
        'numero_parcela',
        'valor_parcela_cents',
        'data_emissao',
        'data_vencimento',
        'pago_em',
        'status',
        'forma_pagamento',
        'observacoes',
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

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
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
