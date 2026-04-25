<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            if (! Schema::hasColumn('clinics', 'code')) {
                $table->string('code', 20)->nullable()->after('company_id');
            }
        });

        $companies = DB::table('companies')
            ->select('id', 'code', 'name', 'legal_name', 'cnpj', 'email', 'phone', 'active')
            ->orderBy('id')
            ->get();

        $tablesToReassign = [
            'units',
            'services',
            'appointments',
            'contas_receber',
            'contas_pagar',
            'categorias_financeiras',
            'contas_bancarias',
            'fluxo_caixa',
            'clinic_insurance_contracts',
            'clinic_responsibles',
            'clinic_partners',
        ];

        $singleRowTables = [
            'clinic_contacts',
            'clinic_certificates',
            'clinic_tax_profiles',
            'clinic_health_regulations',
            'clinic_bank_accounts',
        ];

        foreach ($companies as $company) {
            $clinics = DB::table('clinics')
                ->where('company_id', $company->id)
                ->orderBy('id')
                ->get();

            if ($clinics->isEmpty()) {
                $primaryId = DB::table('clinics')->insertGetId([
                    'company_id' => $company->id,
                    'code' => $company->code,
                    'name' => $company->name,
                    'legal_name' => $company->legal_name,
                    'cnpj' => $company->cnpj,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'active' => $company->active ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $secondaryIds = collect();
            } else {
                $primaryId = $clinics->first()->id;
                $secondaryIds = $clinics->pluck('id')->slice(1)->values();
            }

            DB::table('clinics')->where('id', $primaryId)->update([
                'code' => $company->code,
                'name' => $company->name,
                'legal_name' => $company->legal_name,
                'cnpj' => $company->cnpj,
                'email' => $company->email,
                'phone' => $company->phone,
                'active' => $company->active ?? true,
            ]);

            if ($secondaryIds->isEmpty()) {
                continue;
            }

            foreach ($tablesToReassign as $table) {
                DB::table($table)
                    ->whereIn('clinic_id', $secondaryIds)
                    ->update(['clinic_id' => $primaryId]);
            }

            DB::table('clinic_user')
                ->whereIn('clinic_id', $secondaryIds)
                ->update(['clinic_id' => $primaryId]);

            DB::statement('
                DELETE cu1 FROM clinic_user cu1
                INNER JOIN clinic_user cu2
                    ON cu1.clinic_id = cu2.clinic_id
                    AND cu1.user_id = cu2.user_id
                    AND cu1.id > cu2.id
                WHERE cu1.clinic_id = ?
            ', [$primaryId]);

            foreach ($singleRowTables as $table) {
                $primaryRow = DB::table($table)->where('clinic_id', $primaryId)->first();
                if ($primaryRow) {
                    DB::table($table)->whereIn('clinic_id', $secondaryIds)->delete();
                    continue;
                }

                $candidate = DB::table($table)
                    ->whereIn('clinic_id', $secondaryIds)
                    ->orderBy('id')
                    ->first();

                if ($candidate) {
                    DB::table($table)->where('id', $candidate->id)->update(['clinic_id' => $primaryId]);
                    DB::table($table)
                        ->whereIn('clinic_id', $secondaryIds)
                        ->where('id', '!=', $candidate->id)
                        ->delete();
                }
            }

            $settings = DB::table('clinic_settings')->whereIn('clinic_id', $secondaryIds)->get();
            foreach ($settings as $setting) {
                $exists = DB::table('clinic_settings')
                    ->where('clinic_id', $primaryId)
                    ->where('key', $setting->key)
                    ->exists();
                if ($exists) {
                    DB::table('clinic_settings')->where('id', $setting->id)->delete();
                } else {
                    DB::table('clinic_settings')->where('id', $setting->id)->update([
                        'clinic_id' => $primaryId,
                    ]);
                }
            }

            DB::table('clinics')->whereIn('id', $secondaryIds)->delete();
        }

        Schema::table('clinics', function (Blueprint $table) {
            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropUnique(['company_id']);
            if (Schema::hasColumn('clinics', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};
