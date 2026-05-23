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
        'business_activity',
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

    public static function businessActivityOptions(): array
    {
        return [
            'salao_barbearia' => 'Salão / Barbearia',
            'pet_shop' => 'Pet Shop',
            'estetica_tatuagem' => 'Estética e Tatuagem',
            'automotivo' => 'Automotivo',
            'aulas_treinamentos' => 'Aulas / Treinamentos',
            'outros' => 'Outros',
        ];
    }

    public static function defaultBusinessActivity(): string
    {
        return 'salao_barbearia';
    }

    public static function themePalette(?string $businessActivity): array
    {
        return match ($businessActivity ?: self::defaultBusinessActivity()) {
            'pet_shop' => [
                25 => '#fffaf3',
                50 => '#fff3e1',
                100 => '#ffe4bd',
                200 => '#f7c982',
                300 => '#eda84a',
                400 => '#d98924',
                500 => '#b86b16',
                600 => '#935012',
                700 => '#743d12',
                800 => '#5e3214',
                900 => '#4f2c16',
                950 => '#2d1608',
            ],
            'estetica_tatuagem' => [
                25 => '#fafafa',
                50 => '#f4f4f5',
                100 => '#e4e4e7',
                200 => '#d4d4d8',
                300 => '#a1a1aa',
                400 => '#71717a',
                500 => '#3f3f46',
                600 => '#27272a',
                700 => '#18181b',
                800 => '#111113',
                900 => '#09090b',
                950 => '#030305',
            ],
            'automotivo' => [
                25 => '#f5f9ff',
                50 => '#eff6ff',
                100 => '#dbeafe',
                200 => '#bfdbfe',
                300 => '#93c5fd',
                400 => '#60a5fa',
                500 => '#2563eb',
                600 => '#1d4ed8',
                700 => '#1e40af',
                800 => '#1e3a8a',
                900 => '#172554',
                950 => '#0b163f',
            ],
            'aulas_treinamentos' => [
                25 => '#f0fdfa',
                50 => '#ccfbf1',
                100 => '#99f6e4',
                200 => '#5eead4',
                300 => '#2dd4bf',
                400 => '#14b8a6',
                500 => '#0f9f8f',
                600 => '#0d8276',
                700 => '#0f6760',
                800 => '#11534e',
                900 => '#134541',
                950 => '#082f2d',
            ],
            'outros' => [
                25 => '#f5fbfc',
                50 => '#e8f3f6',
                100 => '#cfe7ed',
                200 => '#a3d0dc',
                300 => '#6fadbf',
                400 => '#438b9d',
                500 => '#256d7f',
                600 => '#1f5969',
                700 => '#1e4855',
                800 => '#1d3d48',
                900 => '#1a333d',
                950 => '#0d2028',
            ],
            default => [
                25 => '#fdf7fc',
                50 => '#fbecf8',
                100 => '#f7d7f0',
                200 => '#efaddf',
                300 => '#df78c8',
                400 => '#c63aa6',
                500 => '#a81d8e',
                600 => '#8b197f',
                700 => '#65116f',
                800 => '#4b0f63',
                900 => '#3b0a55',
                950 => '#26043a',
            ],
        };
    }

    public static function themeCssVariables(?string $businessActivity): string
    {
        $palette = self::themePalette($businessActivity);
        $lines = [];

        foreach ($palette as $weight => $color) {
            $lines[] = "--color-brand-{$weight}: {$color};";
            $lines[] = "--color-blue-light-{$weight}: {$color};";
        }

        $ring = self::hexToRgb($palette[500] ?? '#a81d8e');
        $lines[] = "--shadow-focus-ring: 0px 0px 0px 4px rgba({$ring}, 0.14);";

        return implode("\n", $lines);
    }

    private static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return implode(', ', [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ]);
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
