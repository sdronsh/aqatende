<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Professional;
use App\Models\Specialty;
use App\Models\Unit;
use App\Models\User;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfessionalWebController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $query = Professional::query()
            ->with(['specialties', 'units', 'user', 'services'])
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereHas('user.companies', function ($companyQuery) use ($companyId) {
                        $companyQuery->where('companies.id', $companyId);
                    });
            })
            ->orderBy('display_name');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhereHas('specialties', function ($specialtyQuery) use ($search) {
                        $specialtyQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('services', function ($serviceQuery) use ($search) {
                        $serviceQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $request->string('per_page', '10')->toString();
        if (! in_array($perPage, ['10', '20', '50', 'all'], true)) {
            $perPage = '10';
        }

        $professionals = $perPage === 'all'
            ? $query->get()
            : $query->paginate((int) $perPage)->withQueryString();

        return view('professionals.index', [
            'professionals' => $professionals,
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $users = User::query()
            ->whereHas('companies', function ($companyQuery) use ($companyId) {
                $companyQuery->where('companies.id', $companyId);
            })
            ->orderBy('name')
            ->get();

        $clinics = Clinic::where('company_id', $companyId)->orderBy('name')->get();
        $units = Unit::whereIn('clinic_id', $clinics->pluck('id'))->orderBy('name')->get();
        $specialties = Specialty::where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get();
        $services = Service::whereIn('clinic_id', $clinics->pluck('id'))
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('professionals.create', [
            'users' => $users,
            'units' => $units,
            'specialties' => $specialties,
            'services' => $services,
            'schedulesByWeekday' => $this->buildSchedulesByWeekday(collect()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'unique:professionals,user_id'],
            'display_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'crm_number' => ['nullable', 'string', 'max:30'],
            'crm_state' => ['nullable', 'string', 'size:2'],
            'rqe' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
            'salary_type' => ['required', Rule::in(['fixed', 'commission', 'fixed_plus_commission'])],
            'fixed_salary' => ['nullable'],
            'commission_type' => ['nullable', Rule::in(['percentage', 'fixed_value'])],
            'commission_value' => ['nullable', 'numeric', 'min:0'],
            'specialties' => ['array'],
            'specialties.*' => ['integer', Rule::exists('specialties', 'id')->where('company_id', $companyId)],
            'services' => ['array'],
            'services.*' => ['integer', 'exists:services,id'],
            'units' => ['array'],
            'units.*' => ['integer', 'exists:units,id'],
            'schedules' => ['array'],
        ], [
            'display_name.required' => 'Informe o nome exibido.',
        ]);

        if (! empty($data['user_id'])) {
            $userOk = User::whereKey($data['user_id'])
                ->whereHas('companies', function ($companyQuery) use ($companyId) {
                    $companyQuery->where('companies.id', $companyId);
                })
                ->exists();

            if (! $userOk) {
                return back()->withErrors([
                    'user_id' => 'Usuario nao pertence a empresa selecionada.',
                ])->withInput();
            }
        }

        if (! empty($data['units'])) {
            $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
            $unitsOk = Unit::whereIn('clinic_id', $clinicIds)
                ->whereIn('id', $data['units'])
                ->count() === count($data['units']);
            if (! $unitsOk) {
                return back()->withErrors([
                    'units' => 'Unidade invalida para esta empresa.',
                ])->withInput();
            }
        }

        $scheduleResult = $this->buildSchedules($data['schedules'] ?? [], $companyId, $data['units'] ?? []);
        if (! empty($scheduleResult['errors'])) {
            return back()->withErrors($scheduleResult['errors'])->withInput();
        }

        $data['company_id'] = $companyId;
        $data['fixed_salary_cents'] = $this->moneyToCents($data['fixed_salary'] ?? 0);
        unset($data['fixed_salary']);

        $professional = Professional::create(collect($data)->except('specialties', 'services', 'units', 'schedules')->toArray());
        $professional->specialties()->sync($data['specialties'] ?? []);
        $professional->services()->syncWithPivotValues($data['services'] ?? [], ['active' => true]);
        $professional->units()->sync($data['units'] ?? []);
        $professional->schedules()->createMany($scheduleResult['items']);

        return redirect()->route('professionals.index')->with('status', 'Profissional criado.');
    }

    public function edit(Request $request, Professional $professional): View
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $belongs = (int) $professional->company_id === (int) $companyId
            || ($professional->user
                ? $professional->user->companies()->where('companies.id', $companyId)->exists()
                : false);
        if (! $belongs) {
            abort(403);
        }

        $users = User::query()
            ->whereHas('companies', function ($companyQuery) use ($companyId) {
                $companyQuery->where('companies.id', $companyId);
            })
            ->orderBy('name')
            ->get();

        $clinics = Clinic::where('company_id', $companyId)->orderBy('name')->get();
        $units = Unit::whereIn('clinic_id', $clinics->pluck('id'))->orderBy('name')->get();
        $specialties = Specialty::where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get();
        $services = Service::whereIn('clinic_id', $clinics->pluck('id'))
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('professionals.edit', [
            'professional' => $professional->load(['specialties', 'units', 'services']),
            'users' => $users,
            'units' => $units,
            'specialties' => $specialties,
            'services' => $services,
            'schedulesByWeekday' => $this->buildSchedulesByWeekday($professional->schedules()->get()),
        ]);
    }

    public function update(Request $request, Professional $professional): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $belongs = (int) $professional->company_id === (int) $companyId
            || ($professional->user
                ? $professional->user->companies()->where('companies.id', $companyId)->exists()
                : false);
        if (! $belongs) {
            abort(403);
        }

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'unique:professionals,user_id,'.$professional->id],
            'display_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'crm_number' => ['nullable', 'string', 'max:30'],
            'crm_state' => ['nullable', 'string', 'size:2'],
            'rqe' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
            'salary_type' => ['required', Rule::in(['fixed', 'commission', 'fixed_plus_commission'])],
            'fixed_salary' => ['nullable'],
            'commission_type' => ['nullable', Rule::in(['percentage', 'fixed_value'])],
            'commission_value' => ['nullable', 'numeric', 'min:0'],
            'specialties' => ['array'],
            'specialties.*' => ['integer', Rule::exists('specialties', 'id')->where('company_id', $companyId)],
            'services' => ['array'],
            'services.*' => ['integer', 'exists:services,id'],
            'units' => ['array'],
            'units.*' => ['integer', 'exists:units,id'],
            'schedules' => ['array'],
        ], [
            'display_name.required' => 'Informe o nome exibido.',
        ]);

        if (! empty($data['user_id'])) {
            $userOk = User::whereKey($data['user_id'])
                ->whereHas('companies', function ($companyQuery) use ($companyId) {
                    $companyQuery->where('companies.id', $companyId);
                })
                ->exists();

            if (! $userOk) {
                return back()->withErrors([
                    'user_id' => 'Usuario nao pertence a empresa selecionada.',
                ])->withInput();
            }
        }

        if (! empty($data['units'])) {
            $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
            $unitsOk = Unit::whereIn('clinic_id', $clinicIds)
                ->whereIn('id', $data['units'])
                ->count() === count($data['units']);
            if (! $unitsOk) {
                return back()->withErrors([
                    'units' => 'Unidade invalida para esta empresa.',
                ])->withInput();
            }
        }

        $scheduleResult = $this->buildSchedules($data['schedules'] ?? [], $companyId, $data['units'] ?? []);
        if (! empty($scheduleResult['errors'])) {
            return back()->withErrors($scheduleResult['errors'])->withInput();
        }

        $data['company_id'] = $companyId;
        $data['fixed_salary_cents'] = $this->moneyToCents($data['fixed_salary'] ?? 0);
        unset($data['fixed_salary']);

        $professional->update(collect($data)->except('specialties', 'services', 'units', 'schedules')->toArray());
        $professional->specialties()->sync($data['specialties'] ?? []);
        $professional->services()->syncWithPivotValues($data['services'] ?? [], ['active' => true]);
        $professional->units()->sync($data['units'] ?? []);
        $professional->schedules()->delete();
        $professional->schedules()->createMany($scheduleResult['items']);

        return redirect()->route('professionals.index')->with('status', 'Profissional atualizado.');
    }

    public function destroy(Request $request, Professional $professional): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $belongs = $professional->user
            ? $professional->user->companies()->where('companies.id', $companyId)->exists()
            : false;
        if (! $belongs) {
            abort(403);
        }

        $professional->delete();

        return redirect()->route('professionals.index')->with('status', 'Profissional removido.');
    }

    private function buildSchedules(array $schedules, int $companyId, array $selectedUnits): array
    {
        $allowedWeekdays = [1, 2, 3, 4, 5, 6, 7];
        $errors = [];
        $items = [];
        $selectedUnits = array_map('intval', $selectedUnits);

        foreach ($schedules as $weekday => $schedule) {
            $weekday = (int) $weekday;
            if (! in_array($weekday, $allowedWeekdays, true)) {
                continue;
            }

            $unitId = $schedule['unit_id'] ?? null;
            $slot1 = $schedule['slot1'] ?? [];
            $slot2 = $schedule['slot2'] ?? [];
            $startTime1 = $slot1['start_time'] ?? null;
            $endTime1 = $slot1['end_time'] ?? null;
            $startTime2 = $slot2['start_time'] ?? null;
            $endTime2 = $slot2['end_time'] ?? null;
            $isActive = filter_var($schedule['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $hasAny = $isActive || $unitId || $startTime1 || $endTime1 || $startTime2 || $endTime2;

            if (! $hasAny) {
                continue;
            }

            $slots = [
                ['start' => $startTime1, 'end' => $endTime1, 'key' => 'slot1'],
                ['start' => $startTime2, 'end' => $endTime2, 'key' => 'slot2'],
            ];
            $validSlots = [];
            foreach ($slots as $slot) {
                $hasStart = $slot['start'] !== null && $slot['start'] !== '';
                $hasEnd = $slot['end'] !== null && $slot['end'] !== '';
                if (! $hasStart && ! $hasEnd) {
                    continue;
                }
                if (! $hasStart || ! $hasEnd) {
                    $errors["schedules.$weekday.{$slot['key']}.start_time"] = 'Informe o horario inicial e final.';
                    continue;
                }
                if ($slot['start'] >= $slot['end']) {
                    $errors["schedules.$weekday.{$slot['key']}.end_time"] = 'Horario final deve ser maior que o inicial.';
                    continue;
                }
                $validSlots[] = $slot;
            }

            if (empty($validSlots)) {
                $errors["schedules.$weekday.slot1.start_time"] = 'Informe pelo menos uma faixa de horario.';
            }
            $isActive = $isActive || ! empty($validSlots);

            if ($unitId) {
                $unitOk = Unit::whereKey($unitId)
                    ->whereHas('clinic', fn ($q) => $q->where('company_id', $companyId))
                    ->exists();
                if (! $unitOk) {
                    $errors["schedules.$weekday.unit_id"] = 'Unidade invalida para a empresa.';
                }
            } elseif (empty($selectedUnits)) {
                $errors["schedules.$weekday.unit_id"] = 'Selecione pelo menos uma unidade no cadastro.';
            }

            $weekErrors = array_filter(array_keys($errors), fn ($key) => str_starts_with($key, "schedules.$weekday."));
            if (empty($weekErrors)) {
                $targetUnits = $unitId ? [(int) $unitId] : $selectedUnits;
                foreach ($targetUnits as $targetUnit) {
                    foreach ($validSlots as $slot) {
                        $items[] = [
                            'weekday' => $weekday,
                            'unit_id' => (int) $targetUnit,
                            'start_time' => $slot['start'],
                            'end_time' => $slot['end'],
                            'is_active' => $isActive,
                        ];
                    }
                }
            }
        }

        return ['items' => $items, 'errors' => $errors];
    }

    private function buildSchedulesByWeekday($schedules): array
    {
        $grouped = $schedules->groupBy('weekday');
        $result = [];

        foreach ($grouped as $weekday => $items) {
            $items = $items->sortBy('start_time')->values();
            $unitIds = $items->pluck('unit_id')->unique()->values();
            $unitId = $unitIds->count() === 1 ? $unitIds->first() : null;
            $slots = [
                'slot1' => null,
                'slot2' => null,
            ];
            if ($items->get(0)) {
                $slots['slot1'] = $items->get(0);
            }
            if ($items->get(1)) {
                $slots['slot2'] = $items->get(1);
            }
            $result[$weekday] = [
                'unit_id' => $unitId,
                'is_active' => $items->contains(fn ($item) => (bool) $item->is_active),
                'slot1' => $slots['slot1'],
                'slot2' => $slots['slot2'],
            ];
        }

        return $result;
    }

    private function moneyToCents(mixed $value): int
    {
        $normalized = str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $value));

        return max((int) round(((float) $normalized) * 100), 0);
    }
}
