<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $term = $request->string('q')->trim()->toString();
        $location = $request->string('location')->trim()->toString();

        $clinics = collect();
        $professionals = collect();

        if ($term !== '' || $location !== '') {
            $clinics = Clinic::query()
                ->where('active', true)
                ->when($term !== '', function ($query) use ($term) {
                    $query->where(function ($q) use ($term) {
                        $q->where('name', 'like', "%{$term}%")
                            ->orWhere('trade_name', 'like', "%{$term}%")
                            ->orWhere('legal_name', 'like', "%{$term}%")
                            ->orWhereHas('units.specialties', function ($specialtyQuery) use ($term) {
                                $specialtyQuery->where('name', 'like', "%{$term}%");
                            });
                    });
                })
                ->when($location !== '', function ($query) use ($location) {
                    $query->whereHas('units', function ($unitQuery) use ($location) {
                        $unitQuery->where('city', 'like', "%{$location}%")
                            ->orWhere('state', 'like', "%{$location}%")
                            ->orWhere('address_line1', 'like', "%{$location}%");
                    });
                })
                ->with(['units' => function ($query) {
                    $query->orderBy('name');
                }])
                ->orderBy('name')
                ->limit(12)
                ->get();

            $professionals = Professional::query()
                ->where('active', true)
                ->when($term !== '', function ($query) use ($term) {
                    $query->where(function ($q) use ($term) {
                        $q->where('display_name', 'like', "%{$term}%")
                            ->orWhereHas('specialties', function ($specialtyQuery) use ($term) {
                                $specialtyQuery->where('name', 'like', "%{$term}%");
                            });
                    });
                })
                ->when($location !== '', function ($query) use ($location) {
                    $query->whereHas('units', function ($unitQuery) use ($location) {
                        $unitQuery->where('city', 'like', "%{$location}%")
                            ->orWhere('state', 'like', "%{$location}%")
                            ->orWhere('address_line1', 'like', "%{$location}%");
                    });
                })
                ->with(['specialties', 'units.clinic'])
                ->orderBy('display_name')
                ->limit(12)
                ->get();
        }

        return view('search.results', [
            'term' => $term,
            'location' => $location,
            'clinics' => $clinics,
            'professionals' => $professionals,
        ]);
    }
}
