<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'schedule_start_time')) {
                $table->dropColumn('schedule_start_time');
            }
            if (Schema::hasColumn('companies', 'schedule_end_time')) {
                $table->dropColumn('schedule_end_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->time('schedule_start_time')->nullable()->after('phone');
            $table->time('schedule_end_time')->nullable()->after('schedule_start_time');
        });
    }
};
