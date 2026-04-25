<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_bancarias', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('clinic_id')->constrained()->nullOnDelete();
        });

        $accounts = DB::table('contas_bancarias')->select('id', 'clinic_id')->get();
        $unitByClinic = DB::table('units')
            ->select('id', 'clinic_id')
            ->orderBy('id')
            ->get()
            ->groupBy('clinic_id')
            ->map(fn ($rows) => $rows->first()->id);

        foreach ($accounts as $account) {
            $unitId = $unitByClinic[$account->clinic_id] ?? null;
            if ($unitId) {
                DB::table('contas_bancarias')
                    ->where('id', $account->id)
                    ->update(['unit_id' => $unitId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('contas_bancarias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};
