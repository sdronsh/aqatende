<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('services', 'shared_service')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            $table->boolean('shared_service')->default(false)->after('active');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('services', 'shared_service')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('shared_service');
        });
    }
};
