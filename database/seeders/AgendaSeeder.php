<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Service;
use App\Models\ScheduleBlock;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AgendaSeeder extends Seeder
{
    public function run(): void
    {
        $companyClinic = Clinic::query()->first();
        $unit = Unit::query()->first();
        $services = Service::query()->get();
        $professionals = Professional::query()->get();
        $patients = Patient::query()->get();

        if (! $companyClinic || ! $unit || $services->isEmpty() || $professionals->isEmpty() || $patients->isEmpty()) {
            $this->command?->warn('Dados insuficientes para gerar agenda.');
            return;
        }

        $startDate = Carbon::now()->startOfDay()->addDay();
        $days = 7;

        foreach ($professionals as $professional) {
            for ($d = 0; $d < $days; $d++) {
                $day = $startDate->copy()->addDays($d);
                $baseTime = $day->copy()->setTime(9, 0);
                $slotCount = 4;

                for ($i = 0; $i < $slotCount; $i++) {
                    $service = $services->random();
                    $patient = $patients->random();
                    $scheduledAt = $baseTime->copy()->addMinutes($i * 90);
                    $endsAt = $scheduledAt->copy()->addMinutes($service->duration_minutes);

                    Appointment::updateOrCreate(
                        [
                            'professional_id' => $professional->id,
                            'scheduled_at' => $scheduledAt,
                        ],
                        [
                            'clinic_id' => $companyClinic->id,
                            'unit_id' => $unit->id,
                            'patient_id' => $patient->id,
                            'service_id' => $service->id,
                            'status' => 'scheduled',
                            'channel' => $service->modality ?? 'presencial',
                            'ends_at' => $endsAt,
                            'price_cents' => $service->price_cents,
                            'payment_status' => 'pending',
                        ]
                    );
                }

                // Sem bloqueios padrão; o período será controlado pelo horário do profissional.
            }
        }
    }
}
