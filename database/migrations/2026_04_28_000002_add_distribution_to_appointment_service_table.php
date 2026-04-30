<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_service', function (Blueprint $table) {
            $table->foreignId('professional_id')->nullable()->after('service_id')->constrained()->nullOnDelete();
            $table->dateTime('scheduled_at')->nullable()->after('price_cents');
            $table->dateTime('ends_at')->nullable()->after('scheduled_at');
            $table->string('status', 30)->nullable()->after('ends_at');
            $table->unsignedBigInteger('commission_amount_cents')->default(0)->after('status');
        });

        DB::table('appointments')
            ->whereNotNull('service_id')
            ->orderBy('id')
            ->select(['id', 'professional_id', 'scheduled_at', 'ends_at', 'status'])
            ->chunkById(500, function ($appointments): void {
                foreach ($appointments as $appointment) {
                    DB::table('appointment_service')
                        ->where('appointment_id', $appointment->id)
                        ->update([
                            'professional_id' => $appointment->professional_id,
                            'scheduled_at' => $appointment->scheduled_at,
                            'ends_at' => $appointment->ends_at,
                            'status' => $appointment->status,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('appointment_service', function (Blueprint $table) {
            $table->dropConstrainedForeignId('professional_id');
            $table->dropColumn(['scheduled_at', 'ends_at', 'status', 'commission_amount_cents']);
        });
    }
};
