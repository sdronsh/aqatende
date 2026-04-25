<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicTaxProfile extends Model
{
    protected $fillable = [
        'clinic_id',
        'tax_regime',
        'option_date',
        'iss_rate',
        'service_list_lc116',
        'service_code_municipal',
        'iss_withheld',
        'irrf_rate',
        'pis_cofins_csll_rate',
        'inss_rate',
        'nfse_service_code',
        'nfse_operation_nature',
        'iss_taxation_type',
        'special_tax_regime',
        'environment',
        'city_token',
        'nfse_series',
        'nfse_initial_number',
    ];

    protected $casts = [
        'option_date' => 'date',
        'iss_withheld' => 'bool',
        'iss_rate' => 'decimal:2',
        'irrf_rate' => 'decimal:2',
        'pis_cofins_csll_rate' => 'decimal:2',
        'inss_rate' => 'decimal:2',
        'nfse_initial_number' => 'int',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
