<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\ScheduleBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ScheduleBlockWebController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()?->is_platform_admin && ! $request->session()->get('active_company_id')) {
            return redirect()->route('admin.company-select');
        }

        $companyId = (int) $request->session()->get('active_company_id');
        $professionalId = $request->integer('professional_id') ?: null;

        $professionals = $this->professionalsForCompany($companyId)->get();

        $blocks = ScheduleBlock::query()
            ->with('professional')
            ->whereIn('professional_id', $professionals->pluck('id'))
            ->when($professionalId, fn ($query) => $query->where('professional_id', $professionalId))
            ->where('ends_at', '>=', now()->startOfDay())
            ->orderBy('starts_at')
            ->paginate(15)
            ->withQueryString();

        return view('schedule-blocks.index', [
            'blocks' => $blocks,
            'filters' => ['professional_id' => $professionalId],
            'professionals' => $professionals,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = (int) $request->session()->get('active_company_id');

        $data = $request->validate([
            'professional_id' => ['required', 'integer'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'all_day' => ['nullable', 'boolean'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_unless:all_day,1'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_unless:all_day,1', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $professional = $this->professionalsForCompany($companyId)
            ->whereKey($data['professional_id'])
            ->firstOrFail();

        $date = Carbon::parse($data['date']);
        $allDay = (bool) ($data['all_day'] ?? false);
        $startsAt = $allDay
            ? $date->copy()->startOfDay()
            : $date->copy()->setTimeFromTimeString($data['start_time']);
        $endsAt = $allDay
            ? $date->copy()->endOfDay()
            : $date->copy()->setTimeFromTimeString($data['end_time']);

        ScheduleBlock::create([
            'professional_id' => $professional->id,
            'unit_id' => null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'reason' => $data['reason'] ?: 'Bloqueio de agenda',
        ]);

        return redirect()
            ->route('schedule-blocks.index')
            ->with('status', 'Bloqueio de agenda criado.');
    }

    public function destroy(Request $request, ScheduleBlock $scheduleBlock): RedirectResponse
    {
        $companyId = (int) $request->session()->get('active_company_id');
        abort_unless(
            $this->professionalsForCompany($companyId)->whereKey($scheduleBlock->professional_id)->exists(),
            403
        );

        $scheduleBlock->delete();

        return redirect()
            ->route('schedule-blocks.index')
            ->with('status', 'Bloqueio de agenda removido.');
    }

    private function professionalsForCompany(int $companyId)
    {
        return Professional::query()
            ->where('active', true)
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereHas('user.companies', fn ($companyQuery) => $companyQuery->where('companies.id', $companyId));
            })
            ->orderBy('display_name');
    }
}
