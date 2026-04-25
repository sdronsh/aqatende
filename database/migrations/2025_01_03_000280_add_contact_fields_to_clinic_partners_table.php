<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_partners', function (Blueprint $table) {
            $table->string('email')->nullable()->after('cpf');
            $table->string('phone', 30)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_partners', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone']);
        });
    }
};
