<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        $companyId = Session::get('active_company_id');
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.servicos.view');
    }

    public function view(User $user, Service $service): bool
    {
        $companyId = $service->clinic?->company_id;
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.servicos.view');
    }

    public function create(User $user): bool
    {
        $companyId = Session::get('active_company_id');
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.servicos.create');
    }

    public function update(User $user, Service $service): bool
    {
        $companyId = $service->clinic?->company_id;
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.servicos.update');
    }

    public function delete(User $user, Service $service): bool
    {
        $companyId = $service->clinic?->company_id;
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'cadastro.servicos.delete');
    }
}
