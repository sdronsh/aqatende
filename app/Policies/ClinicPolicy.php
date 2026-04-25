<?php

namespace App\Policies;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class ClinicPolicy
{
    public function viewAny(User $user): bool
    {
        $companyId = Session::get('active_company_id');
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.clinicas.view');
    }

    public function view(User $user, Clinic $clinic): bool
    {
        if (! $clinic->company_id) {
            return false;
        }

        return $user->hasCompanyPermission($clinic->company_id, 'cadastro.clinicas.view');
    }

    public function create(User $user): bool
    {
        $companyId = Session::get('active_company_id');
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.clinicas.create');
    }

    public function update(User $user, Clinic $clinic): bool
    {
        if (! $clinic->company_id) {
            return false;
        }

        return $user->hasCompanyPermission($clinic->company_id, 'cadastro.clinicas.update');
    }

    public function delete(User $user, Clinic $clinic): bool
    {
        if (! $clinic->company_id) {
            return false;
        }

        return $user->hasCompanyPermission($clinic->company_id, 'cadastro.clinicas.delete');
    }
}
