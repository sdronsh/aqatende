<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique('specialties_name_unique');
        });

        $defaultCompanyId = DB::table('companies')->orderBy('id')->value('id');

        if (! $defaultCompanyId && DB::table('specialties')->exists()) {
            throw new RuntimeException('Nao foi possivel vincular especialidades por empresa: nenhuma empresa encontrada.');
        }

        if ($defaultCompanyId) {
            $specialties = DB::table('specialties')
                ->select('id', 'name', 'active', 'created_at', 'updated_at')
                ->orderBy('id')
                ->get();

            $professionalCompaniesFromUnits = [];
            $professionalUnitRows = DB::table('professional_unit as pu')
                ->join('units as u', 'u.id', '=', 'pu.unit_id')
                ->join('clinics as c', 'c.id', '=', 'u.clinic_id')
                ->select('pu.professional_id', 'c.company_id')
                ->distinct()
                ->get();
            foreach ($professionalUnitRows as $row) {
                $professionalId = (int) $row->professional_id;
                $companyId = (int) $row->company_id;
                $professionalCompaniesFromUnits[$professionalId][$companyId] = true;
            }

            $professionalCompaniesFromUsers = [];
            $professionalUserRows = DB::table('professionals as p')
                ->join('company_user as cu', 'cu.user_id', '=', 'p.user_id')
                ->select('p.id as professional_id', 'cu.company_id')
                ->distinct()
                ->get();
            foreach ($professionalUserRows as $row) {
                $professionalId = (int) $row->professional_id;
                $companyId = (int) $row->company_id;
                $professionalCompaniesFromUsers[$professionalId][$companyId] = true;
            }

            $companyIdsBySpecialty = [];

            $unitRows = DB::table('unit_specialty as us')
                ->join('units as u', 'u.id', '=', 'us.unit_id')
                ->join('clinics as c', 'c.id', '=', 'u.clinic_id')
                ->select('us.specialty_id', 'c.company_id')
                ->distinct()
                ->get();
            foreach ($unitRows as $row) {
                $specialtyId = (int) $row->specialty_id;
                $companyId = (int) $row->company_id;
                $companyIdsBySpecialty[$specialtyId][$companyId] = true;
            }

            $professionalSpecialtyRows = DB::table('professional_specialty')
                ->select('professional_id', 'specialty_id')
                ->get();
            foreach ($professionalSpecialtyRows as $row) {
                $specialtyId = (int) $row->specialty_id;
                $professionalId = (int) $row->professional_id;

                $companies = $professionalCompaniesFromUnits[$professionalId] ?? $professionalCompaniesFromUsers[$professionalId] ?? [];
                if (empty($companies)) {
                    $companies = [$defaultCompanyId => true];
                }

                foreach (array_keys($companies) as $companyId) {
                    $companyIdsBySpecialty[$specialtyId][(int) $companyId] = true;
                }
            }

            $targetSpecialtyByCompany = [];

            foreach ($specialties as $specialty) {
                $specialtyId = (int) $specialty->id;
                $companies = array_keys($companyIdsBySpecialty[$specialtyId] ?? []);
                if (empty($companies)) {
                    $companies = [$defaultCompanyId];
                }

                sort($companies);

                $primaryCompanyId = (int) $companies[0];
                DB::table('specialties')
                    ->where('id', $specialtyId)
                    ->update(['company_id' => $primaryCompanyId]);

                $targetSpecialtyByCompany[$specialtyId][$primaryCompanyId] = $specialtyId;

                foreach (array_slice($companies, 1) as $companyId) {
                    $newId = DB::table('specialties')->insertGetId([
                        'company_id' => (int) $companyId,
                        'name' => $specialty->name,
                        'active' => (bool) $specialty->active,
                        'created_at' => $specialty->created_at,
                        'updated_at' => $specialty->updated_at,
                    ]);

                    $targetSpecialtyByCompany[$specialtyId][(int) $companyId] = (int) $newId;
                }
            }

            $unitPivotRows = DB::table('unit_specialty as us')
                ->join('units as u', 'u.id', '=', 'us.unit_id')
                ->join('clinics as c', 'c.id', '=', 'u.clinic_id')
                ->select('us.id', 'us.unit_id', 'us.specialty_id', 'c.company_id')
                ->get();

            foreach ($unitPivotRows as $row) {
                $pivotId = (int) $row->id;
                $unitId = (int) $row->unit_id;
                $sourceSpecialtyId = (int) $row->specialty_id;
                $companyId = (int) $row->company_id;

                $targetSpecialtyId = $targetSpecialtyByCompany[$sourceSpecialtyId][$companyId]
                    ?? $targetSpecialtyByCompany[$sourceSpecialtyId][$defaultCompanyId]
                    ?? $sourceSpecialtyId;

                if ($targetSpecialtyId === $sourceSpecialtyId) {
                    continue;
                }

                $duplicateExists = DB::table('unit_specialty')
                    ->where('unit_id', $unitId)
                    ->where('specialty_id', $targetSpecialtyId)
                    ->exists();

                if ($duplicateExists) {
                    DB::table('unit_specialty')->where('id', $pivotId)->delete();
                    continue;
                }

                DB::table('unit_specialty')
                    ->where('id', $pivotId)
                    ->update(['specialty_id' => $targetSpecialtyId]);
            }

            $primaryCompanyByProfessional = [];
            $professionalIds = DB::table('professionals')->pluck('id');
            foreach ($professionalIds as $professionalIdRaw) {
                $professionalId = (int) $professionalIdRaw;
                $companySet = $professionalCompaniesFromUnits[$professionalId] ?? $professionalCompaniesFromUsers[$professionalId] ?? [];
                $companies = array_keys($companySet);
                sort($companies);
                $primaryCompanyByProfessional[$professionalId] = ! empty($companies)
                    ? (int) $companies[0]
                    : (int) $defaultCompanyId;
            }

            $professionalPivotRows = DB::table('professional_specialty')
                ->select('id', 'professional_id', 'specialty_id')
                ->get();

            foreach ($professionalPivotRows as $row) {
                $pivotId = (int) $row->id;
                $professionalId = (int) $row->professional_id;
                $sourceSpecialtyId = (int) $row->specialty_id;
                $companyId = $primaryCompanyByProfessional[$professionalId] ?? (int) $defaultCompanyId;

                $targetSpecialtyId = $targetSpecialtyByCompany[$sourceSpecialtyId][$companyId]
                    ?? $targetSpecialtyByCompany[$sourceSpecialtyId][$defaultCompanyId]
                    ?? $sourceSpecialtyId;

                if ($targetSpecialtyId === $sourceSpecialtyId) {
                    continue;
                }

                $duplicateExists = DB::table('professional_specialty')
                    ->where('professional_id', $professionalId)
                    ->where('specialty_id', $targetSpecialtyId)
                    ->exists();

                if ($duplicateExists) {
                    DB::table('professional_specialty')->where('id', $pivotId)->delete();
                    continue;
                }

                DB::table('professional_specialty')
                    ->where('id', $pivotId)
                    ->update(['specialty_id' => $targetSpecialtyId]);
            }
        }

        DB::table('specialties')
            ->whereNull('company_id')
            ->update(['company_id' => $defaultCompanyId]);

        Schema::table('specialties', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->dropUnique('specialties_company_id_name_unique');
            $table->dropConstrainedForeignId('company_id');
            $table->unique('name');
        });
    }
};
