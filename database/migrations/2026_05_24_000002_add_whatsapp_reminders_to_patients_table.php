<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('patients', 'whatsapp_reminders_enabled')) {
            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->boolean('whatsapp_reminders_enabled')->default(true)->after('whatsapp');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('patients', 'whatsapp_reminders_enabled')) {
            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('whatsapp_reminders_enabled');
        });
    }
};
