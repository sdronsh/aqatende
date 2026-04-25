<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('trade_name')->nullable()->after('legal_name');
            $table->string('cnae_main', 20)->nullable()->after('cnpj');
            $table->text('cnae_secondary')->nullable()->after('cnae_main');
            $table->string('legal_nature')->nullable()->after('cnae_secondary');
            $table->string('state_registration', 30)->nullable()->after('legal_nature');
            $table->string('municipal_registration', 30)->nullable()->after('state_registration');
            $table->string('tax_regime', 30)->nullable()->after('municipal_registration');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn([
                'trade_name',
                'cnae_main',
                'cnae_secondary',
                'legal_nature',
                'state_registration',
                'municipal_registration',
                'tax_regime',
            ]);
        });
    }
};
