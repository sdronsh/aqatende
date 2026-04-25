<?php

namespace App\Policies;

use App\Models\Professional;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class ProfessionalPolicy
{
    public function view(User $user, Professional $professional): bool
    {
        if ($user->professional && $user->professional->id === $professional->id) {
            return true;
        }

        $companyId = Session::get('active_company_id');
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.profissionais.view');
    }

    public function update(User $user, Professional $professional): bool
    {
        if ($user->professional && $user->professional->id === $professional->id) {
            return true;
        }

        $companyId = Session::get('active_company_id');
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.profissionais.update');
    }
}
