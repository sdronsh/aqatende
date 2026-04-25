<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'is_platform_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_platform_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['role_id', 'is_master'])
            ->withTimestamps();
    }

    public function professional(): HasOne
    {
        return $this->hasOne(Professional::class);
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function companyRole(int $companyId): ?Role
    {
        $pivot = $this->companies()
            ->whereKey($companyId)
            ->first()?->pivot;

        if (! $pivot?->role_id) {
            return null;
        }

        return Role::find($pivot->role_id);
    }

    public function isCompanyMaster(int $companyId): bool
    {
        return $this->companies()
            ->whereKey($companyId)
            ->wherePivot('is_master', true)
            ->exists();
    }

    public function hasCompanyPermission(int $companyId, string $permissionKey): bool
    {
        if ($this->is_platform_admin || $this->isCompanyMaster($companyId)) {
            return true;
        }

        $role = $this->companyRole($companyId);
        if (! $role) {
            return false;
        }

        return $role->permissions()->where('key', $permissionKey)->exists();
    }

    public function hasAnyCompany(): bool
    {
        return $this->companies()->exists();
    }
}
