<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialCategory extends Model
{
    protected $table = 'categorias_financeiras';

    protected $fillable = [
        'clinic_id',
        'name',
        'type',
        'active',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
