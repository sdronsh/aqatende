<?php

use App\Models\Clinic;
use App\Models\Company;
use App\Models\Unit;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('aqatende:sync-company-clinic {cnpj : CNPJ da empresa usada no login}', function (string $cnpj): int {
    $cnpjDigits = preg_replace('/\D/', '', $cnpj) ?? '';
    if ($cnpjDigits === '') {
        $this->error('Informe um CNPJ valido.');
        return self::FAILURE;
    }

    $company = Company::where('cnpj', $cnpjDigits)
        ->orWhere('cnpj', $cnpj)
        ->first();

    if (! $company) {
        $this->error("Empresa com CNPJ {$cnpj} nao encontrada.");
        return self::FAILURE;
    }

    $clinic = Clinic::updateOrCreate(
        ['company_id' => $company->id],
        [
            'name' => $company->name,
            'legal_name' => $company->legal_name ?: $company->name,
            'trade_name' => $company->name,
            'cnpj' => $cnpjDigits,
            'email' => $company->email,
            'phone' => $company->phone,
            'schedule_start_time' => '08:00:00',
            'schedule_end_time' => '18:00:00',
            'active' => (bool) $company->active,
            'terms_version' => null,
            'terms_accepted_at' => null,
            'terms_accepted_ip' => null,
            'terms_accepted_user_id' => null,
        ]
    );

    $unit = Unit::updateOrCreate(
        ['clinic_id' => $clinic->id, 'name' => 'Unidade Principal'],
        [
            'address_line1' => 'Endereco nao informado',
            'address_line2' => null,
            'city' => 'Cidade',
            'state' => 'SP',
            'zip' => '00000-000',
            'country' => 'BR',
            'phone' => $company->phone,
            'active' => true,
        ]
    );

    $this->info("Empresa sincronizada: {$company->name}");
    $this->line("Company ID: {$company->id}");
    $this->line("Clinic ID: {$clinic->id}");
    $this->line("Unit ID: {$unit->id}");
    $this->line('Termo de uso ficou pendente para aceite no primeiro acesso.');

    return self::SUCCESS;
})->purpose('Cria ou atualiza a clinic operacional a partir de uma company existente pelo CNPJ');
