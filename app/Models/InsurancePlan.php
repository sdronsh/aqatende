<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InsurancePlan extends Model
{
    protected $fillable = [
        'name',
        'active',
    ];

    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class, 'professional_insurance')
            ->withTimestamps()
            ->withPivot('active');
    }
}
