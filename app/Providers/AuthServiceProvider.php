<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Unit;
use App\Policies\AppointmentPolicy;
use App\Policies\ClinicPolicy;
use App\Policies\MedicalRecordPolicy;
use App\Policies\PatientPolicy;
use App\Policies\ProfessionalPolicy;
use App\Policies\ServicePolicy;
use App\Policies\UnitPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Clinic::class => ClinicPolicy::class,
        Unit::class => UnitPolicy::class,
        Service::class => ServicePolicy::class,
        Professional::class => ProfessionalPolicy::class,
        Patient::class => PatientPolicy::class,
        Appointment::class => AppointmentPolicy::class,
        MedicalRecord::class => MedicalRecordPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
