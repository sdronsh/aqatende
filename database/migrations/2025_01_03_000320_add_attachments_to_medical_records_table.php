<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->json('anexos_documentos')->nullable()->after('documentos_gerados');
            $table->json('anexos_exames')->nullable()->after('anexos_documentos');
            $table->json('anexos_receitas')->nullable()->after('anexos_exames');
            $table->json('anexos_laudos')->nullable()->after('anexos_receitas');
            $table->json('anexos_atestados')->nullable()->after('anexos_laudos');
        });
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropColumn([
                'anexos_documentos',
                'anexos_exames',
                'anexos_receitas',
                'anexos_laudos',
                'anexos_atestados',
            ]);
        });
    }
};
