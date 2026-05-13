<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('services', 'whatsapp_booking_enabled')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            $table->boolean('whatsapp_booking_enabled')->default(false)->after('shared_service');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('services', 'whatsapp_booking_enabled')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('whatsapp_booking_enabled');
        });
    }
};
