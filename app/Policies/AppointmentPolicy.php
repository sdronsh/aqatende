<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

class AppointmentPolicy
{
    protected function clinicSetting(Appointment $appointment, string $key, mixed $default): mixed
    {
        $clinic = $appointment->clinic ?? Clinic::find($appointment->clinic_id);

        return $clinic?->getSetting($key, $default) ?? $default;
    }

    public function viewAny(User $user): bool
    {
        $companyId = Session::get('active_company_id');

        return ($companyId && $user->hasCompanyPermission($companyId, 'agendamento.agendamentos.view'))
            || $user->patient !== null
            || $user->professional !== null;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        $companyId = $appointment->clinic?->company_id;
        if ($companyId && $user->hasCompanyPermission($companyId, 'agendamento.agendamentos.view')) {
            return true;
        }

        if ($user->professional && $user->professional->id === $appointment->professional_id) {
            return true;
        }

        return $user->patient && $user->patient->id === $appointment->patient_id;
    }

    public function create(User $user): bool
    {
        $companyId = Session::get('active_company_id');

        return ($companyId && $user->hasCompanyPermission($companyId, 'agendamento.agendamentos.create'))
            || $user->patient !== null;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        $companyId = $appointment->clinic?->company_id;
        if ($companyId && $user->hasCompanyPermission($companyId, 'agendamento.agendamentos.update')) {
            return true;
        }

        if ($user->professional === null) {
            return false;
        }

        if ($user->professional->id === $appointment->professional_id) {
            return true;
        }

        return (bool) $this->clinicSetting(
            $appointment,
            'policy.allow_professional_cross_edit',
            config('aqamed.policy.allow_professional_cross_edit', true)
        );
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        $companyId = $appointment->clinic?->company_id;
        if (! $companyId) {
            return false;
        }

        return $user->hasCompanyPermission($companyId, 'agendamento.agendamentos.delete');
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        $companyId = $appointment->clinic?->company_id;
        if ($companyId && $user->hasCompanyPermission($companyId, 'agendamento.agendamentos.update')) {
            return true;
        }

        if ($user->patient && $user->patient->id === $appointment->patient_id) {
            $hours = (int) $this->clinicSetting(
                $appointment,
                'policy.patient_cancel_hours',
                config('aqamed.policy.patient_cancel_hours', 4)
            );
            $deadline = Carbon::now()->addHours($hours);

            return $appointment->scheduled_at->greaterThan($deadline);
        }

        if ($user->professional === null) {
            return false;
        }

        if ($user->professional->id === $appointment->professional_id) {
            return true;
        }

        return (bool) $this->clinicSetting(
            $appointment,
            'policy.allow_professional_cross_edit',
            config('aqamed.policy.allow_professional_cross_edit', true)
        );
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        $companyId = $appointment->clinic?->company_id;
        if ($companyId && $user->hasCompanyPermission($companyId, 'agendamento.agendamentos.update')) {
            return true;
        }

        if ($user->patient && $user->patient->id === $appointment->patient_id) {
            $hours = (int) $this->clinicSetting(
                $appointment,
                'policy.patient_reschedule_hours',
                config('aqamed.policy.patient_reschedule_hours', 4)
            );
            $deadline = Carbon::now()->addHours($hours);

            return $appointment->scheduled_at->greaterThan($deadline);
        }

        if ($user->professional === null) {
            return false;
        }

        if ($user->professional->id === $appointment->professional_id) {
            return true;
        }

        return (bool) $this->clinicSetting(
            $appointment,
            'policy.allow_professional_cross_edit',
            config('aqamed.policy.allow_professional_cross_edit', true)
        );
    }
}
