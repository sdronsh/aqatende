<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_pagar', function (Blueprint $table) {
            $table->foreignId('professional_id')->nullable()->after('unit_id')->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->after('professional_id')->constrained()->nullOnDelete();
            $table->string('origem', 40)->nullable()->after('observacoes');

            $table->unique('appointment_id');
            $table->index(['professional_id', 'status']);
            $table->index(['origem', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('contas_pagar', function (Blueprint $table) {
            $table->dropUnique(['appointment_id']);
            $table->dropIndex(['professional_id', 'status']);
            $table->dropIndex(['origem', 'status']);
            $table->dropConstrainedForeignId('appointment_id');
            $table->dropConstrainedForeignId('professional_id');
            $table->dropColumn('origem');
        });
    }
};
