<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class PatientPolicy
{
    public function view(User $user, Patient $patient): bool
    {
        if ($user->patient && $user->patient->id === $patient->id) {
            return true;
        }

        $companyId = Session::get('active_company_id');
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.pacientes.view');
    }

    public function update(User $user, Patient $patient): bool
    {
        if ($user->patient && $user->patient->id === $patient->id) {
            return true;
        }

        $companyId = Session::get('active_company_id');
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.pacientes.update');
    }
}
