<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Specialty;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('layout_base/especialidades_medicas_cfm.csv');

        if (! file_exists($path)) {
            $this->command?->error("Arquivo não encontrado: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            $this->command?->error("Não foi possível abrir: {$path}");
            return;
        }

        $row = 0;
        $companies = Company::query()->select('id')->get();
        if ($companies->isEmpty()) {
            fclose($handle);
            $this->command?->warn('Nenhuma empresa encontrada para vincular especialidades.');
            return;
        }

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $row++;
            if ($row === 1) {
                continue;
            }

            $name = trim((string) ($data[0] ?? ''));
            if ($name === '') {
                continue;
            }

            $name = preg_replace('/^\xEF\xBB\xBF/', '', $name);

            foreach ($companies as $company) {
                Specialty::updateOrCreate(
                    ['company_id' => $company->id, 'name' => $name],
                    ['active' => true]
                );
            }
        }

        fclose($handle);
    }
}
