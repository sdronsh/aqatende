<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Appointment::class, 'appointment');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Appointment::query();

        if ($user->patient) {
            $query->where('patient_id', $user->patient->id);
        } elseif ($user->professional) {
            $query->where('professional_id', $user->professional->id);
        } else {
            $companyId = $request->session()->get('active_company_id');
            if ($companyId) {
                $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
                $query->whereIn('clinic_id', $clinicIds);
            }
        }

        return $query->orderByDesc('scheduled_at')->get();
    }

    public function show(Appointment $appointment)
    {
        return $appointment->load('clinic', 'unit', 'professional', 'patient', 'service', 'payments');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'professional_id' => ['required', 'integer', 'exists:professionals,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'status' => ['required', 'string', 'in:agendado,confirmado,atendido,concluido,cancelado,scheduled,confirmed,attended,done,cancelled'],
            'channel' => ['required', 'string', 'max:20'],
            'scheduled_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_first_visit' => ['boolean'],
            'notes' => ['nullable', 'string'],
            'price' => $this->moneyRule('price_cents'),
            'price_cents' => ['required_without:price', 'nullable', 'integer', 'min:0'],
            'payment_status' => ['nullable', 'string', 'max:30'],
        ]);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || ! Clinic::where('company_id', $companyId)->whereKey($data['clinic_id'])->exists()) {
            abort(403);
        }

        $data['status'] = $this->normalizeStatus($data['status']);
        $data['price_cents'] = $this->resolvePriceCents($data);
        unset($data['price']);

        return Appointment::create($data);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'status' => ['sometimes', 'required', 'string', 'in:agendado,confirmado,atendido,concluido,cancelado,scheduled,confirmed,attended,done,cancelled'],
            'channel' => ['sometimes', 'required', 'string', 'max:20'],
            'scheduled_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_first_visit' => ['boolean'],
            'notes' => ['nullable', 'string'],
            'price' => $this->moneyRule('price_cents'),
            'price_cents' => ['required_without:price', 'nullable', 'integer', 'min:0'],
            'payment_status' => ['nullable', 'string', 'max:30'],
        ]);

        if (isset($data['status'])) {
            $data['status'] = $this->normalizeStatus($data['status']);
        }
        if (array_key_exists('price', $data) || array_key_exists('price_cents', $data)) {
            $data['price_cents'] = $this->resolvePriceCents($data);
            unset($data['price']);
        }

        $appointment->update($data);

        return $appointment;
    }

    private function resolvePriceCents(array $data): int
    {
        if (array_key_exists('price', $data) && $data['price'] !== null && $data['price'] !== '') {
            return $this->parsePriceToCents($data['price']);
        }

        if (array_key_exists('price_cents', $data) && $data['price_cents'] !== null && $data['price_cents'] !== '') {
            return (int) $data['price_cents'];
        }

        return 0;
    }

    private function parsePriceToCents(string $value): int
    {
        $raw = str_replace(['R$', 'r$', ' '], '', trim($value));
        if (str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        $amount = is_numeric($raw) ? (float) $raw : 0.0;

        return (int) round($amount * 100);
    }

    private function moneyRule(?string $requiredWithout = null): array
    {
        $rules = ['nullable', 'regex:/^\\d{1,3}(\\.\\d{3})*(,\\d{2})?$|^\\d+([.,]\\d{1,2})?$/'];
        if ($requiredWithout) {
            $rules[] = 'required_without:' . $requiredWithout;
        }

        return $rules;
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->noContent();
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $this->authorize('cancel', $appointment);

        $appointment->update([
            'status' => 'cancelado',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->input('reason'),
        ]);

        return $appointment;
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim($status ?? 'agendado'));
        $map = [
            'scheduled' => 'agendado',
            'confirmed' => 'confirmado',
            'attended' => 'atendido',
            'done' => 'concluido',
            'cancelled' => 'cancelado',
        ];

        return $map[$status] ?? $status;
    }

    public function reschedule(Request $request, Appointment $appointment)
    {
        $this->authorize('reschedule', $appointment);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $appointment->update($data);

        return $appointment;
    }
}
