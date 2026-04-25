<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySelectController extends Controller
{
    public function index(Request $request): View
    {
        $query = Company::query()->orderBy('name');
        $search = $request->string('search')->toString();
        if ($search !== '') {
            $query->where(function ($filter) use ($search): void {
                $filter->where('name', 'like', "%{$search}%")
                    ->orWhere('cnpj', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return view('admin.company-select', [
            'companies' => $query->get(),
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $request->session()->put('active_company_id', (int) $data['company_id']);

        return redirect()->route('dashboard');
    }
}
