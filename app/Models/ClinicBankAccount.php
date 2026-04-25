<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicBankAccount extends Model
{
    protected $fillable = [
        'clinic_id',
        'bank_name',
        'agency',
        'account',
        'account_type',
        'pix_key',
        'financial_responsible_name',
        'financial_responsible_cpf',
        'billing_email',
        'boleto_config',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
