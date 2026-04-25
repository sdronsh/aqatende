<?php

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class MedicalRecordPolicy
{
    public function view(User $user, MedicalRecord $record): bool
    {
        $companyId = Session::get('active_company_id');
        if ($companyId && $user->hasCompanyPermission($companyId, 'cadastro.pacientes.view')) {
            return true;
        }

        if ($user->professional && $user->professional->id === $record->professional_id) {
            return true;
        }

        return $user->patient && $user->patient->id === $record->patient_id;
    }

    public function create(User $user): bool
    {
        $companyId = Session::get('active_company_id');

        return ($companyId && $user->hasCompanyPermission($companyId, 'cadastro.pacientes.update'))
            || $user->professional !== null;
    }

    public function update(User $user, MedicalRecord $record): bool
    {
        $companyId = Session::get('active_company_id');

        return ($companyId && $user->hasCompanyPermission($companyId, 'cadastro.pacientes.update'))
            || ($user->professional && $user->professional->id === $record->professional_id);
    }

    public function delete(User $user, MedicalRecord $record): bool
    {
        $companyId = Session::get('active_company_id');

        return $companyId && $user->hasCompanyPermission($companyId, 'cadastro.pacientes.delete');
    }
}
