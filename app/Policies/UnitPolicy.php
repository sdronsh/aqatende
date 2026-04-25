<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        $companyId = Session::get('active_company_id');
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.unidades.view');
    }

    public function view(User $user, Unit $unit): bool
    {
        $companyId = $unit->clinic?->company_id;
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.unidades.view');
    }

    public function create(User $user): bool
    {
        $companyId = Session::get('active_company_id');
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.unidades.create');
    }

    public function update(User $user, Unit $unit): bool
    {
        $companyId = $unit->clinic?->company_id;
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.unidades.update');
    }

    public function delete(User $user, Unit $unit): bool
    {
        $companyId = $unit->clinic?->company_id;
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.unidades.delete');
    }
}
