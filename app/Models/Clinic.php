<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Term;

class Clinic extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'legal_name',
        'trade_name',
        'cnpj',
        'cnae_main',
        'cnae_secondary',
        'legal_nature',
        'state_registration',
        'municipal_registration',
        'tax_regime',
        'email',
        'phone',
        'schedule_start_time',
        'schedule_end_time',
        'active',
        'terms_version',
        'terms_accepted_at',
        'terms_accepted_ip',
        'terms_accepted_user_id',
    ];

    protected $casts = [
        'terms_accepted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(ClinicSetting::class);
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $value = $this->settings()
            ->where('key', $key)
            ->value('value');

        if ($value === null) {
            return $default;
        }

        $lower = Str::lower($value);
        if (in_array($lower, ['true', 'false'], true)) {
            return $lower === 'true';
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }

    public function contact()
    {
        return $this->hasOne(ClinicContact::class);
    }

    public function certificate()
    {
        return $this->hasOne(ClinicCertificate::class);
    }

    public function taxProfile()
    {
        return $this->hasOne(ClinicTaxProfile::class);
    }

    public function healthRegulation()
    {
        return $this->hasOne(ClinicHealthRegulation::class);
    }

    public function bankAccount()
    {
        return $this->hasOne(ClinicBankAccount::class);
    }

    public function insuranceContracts()
    {
        return $this->hasMany(ClinicInsuranceContract::class);
    }

    public function responsibles()
    {
        return $this->hasMany(ClinicResponsible::class);
    }

    public function partners(): HasMany
    {
        return $this->hasMany(ClinicPartner::class);
    }

    public function termsAcceptedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'terms_accepted_user_id');
    }

    public function hasAcceptedTerms(?string $version = null): bool
    {
        $version = $version ?? Term::currentUsageVersion();

        if (! $version) {
            return false;
        }

        return $this->terms_accepted_at !== null
            && $this->terms_version === $version;
    }
}
