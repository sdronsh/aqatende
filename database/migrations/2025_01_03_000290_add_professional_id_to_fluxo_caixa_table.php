<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fluxo_caixa', function (Blueprint $table) {
            $table->foreignId('professional_id')->nullable()->constrained()->nullOnDelete()->after('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('fluxo_caixa', function (Blueprint $table) {
            $table->dropConstrainedForeignId('professional_id');
        });
    }
};
