<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_partners', function (Blueprint $table) {
            $table->boolean('repasse')->default(false)->after('share_percent');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_partners', function (Blueprint $table) {
            $table->dropColumn('repasse');
        });
    }
};
