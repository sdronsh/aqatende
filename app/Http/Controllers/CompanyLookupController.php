<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyLookupController extends Controller
{
    public function __invoke(Request $request)
    {
        $code = preg_replace('/\D/', '', (string) $request->query('code', '')) ?? '';

        if ($code === '') {
            return response()->json(['name' => null]);
        }

        $company = Company::where('cnpj', $code)->first();

        if (! $company) {
            return response()->json(['name' => null], 404);
        }

        return response()->json([
            'name' => $company->name,
            'code' => $company->cnpj,
        ]);
    }
}
