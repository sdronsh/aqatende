<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $fillable = [
        'name',
        'legal_name',
        'cnpj',
        'license_code',
        'code',
        'email',
        'phone',
        'active',
        'is_demo',
    ];

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'is_master'])
            ->withTimestamps();
    }

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class)
            ->withTimestamps();
    }

    public function patientBookingLinks(): HasMany
    {
        return $this->hasMany(PatientBookingLink::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            if (! $company->code) {
                $company->code = $company->generateCode();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    protected function generateCode(): string
    {
        $base = Str::upper(Str::slug($this->name ?? 'EMPRESA', ''));
        $base = $base !== '' ? $base : 'EMPRESA';
        $base = Str::limit($base, 10, '');
        $suffix = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $code = $base.$suffix;

        while (static::where('code', $code)->exists()) {
            $suffix = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $code = $base.$suffix;
        }

        return $code;
    }
}
