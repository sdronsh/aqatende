<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Service::class, 'service');
    }

    public function index(Request $request)
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            return collect();
        }

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');

        return Service::whereIn('clinic_id', $clinicIds)->get();
    }

    public function show(Service $service)
    {
        return $service->load('clinic', 'unit');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'modality' => ['required', 'string', 'max:20'],
            'price' => $this->moneyRule('price_cents'),
            'price_cents' => ['required_without:price', 'nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
            'shared_service' => ['boolean'],
            'whatsapp_booking_enabled' => ['boolean'],
        ]);

        $companyId = $request->session()->get('active_company_id');
        if (! $companyId || ! Clinic::where('company_id', $companyId)->whereKey($data['clinic_id'])->exists()) {
            abort(403);
        }

        $data['price_cents'] = $this->resolvePriceCents($data);
        $data['active'] = (bool) ($data['active'] ?? false);
        $data['shared_service'] = (bool) ($data['shared_service'] ?? false);
        $data['whatsapp_booking_enabled'] = (bool) ($data['whatsapp_booking_enabled'] ?? false);
        unset($data['price']);

        return Service::create($data);
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'min:5', 'max:480'],
            'modality' => ['sometimes', 'required', 'string', 'max:20'],
            'price' => $this->moneyRule('price_cents'),
            'price_cents' => ['required_without:price', 'nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
            'shared_service' => ['boolean'],
            'whatsapp_booking_enabled' => ['boolean'],
        ]);

        $data['price_cents'] = $this->resolvePriceCents($data);
        if (array_key_exists('active', $data)) {
            $data['active'] = (bool) $data['active'];
        }
        if (array_key_exists('shared_service', $data)) {
            $data['shared_service'] = (bool) $data['shared_service'];
        }
        if (array_key_exists('whatsapp_booking_enabled', $data)) {
            $data['whatsapp_booking_enabled'] = (bool) $data['whatsapp_booking_enabled'];
        }
        unset($data['price']);

        $service->update($data);

        return $service;
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return response()->noContent();
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
}
