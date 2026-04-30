<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedBigInteger('price_cents')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['appointment_id', 'service_id']);
        });

        DB::table('appointments')
            ->whereNotNull('service_id')
            ->orderBy('id')
            ->select(['id', 'service_id', 'duration_minutes', 'price_cents', 'created_at', 'updated_at'])
            ->chunkById(500, function ($appointments): void {
                $rows = $appointments->map(fn ($appointment) => [
                    'appointment_id' => $appointment->id,
                    'service_id' => $appointment->service_id,
                    'duration_minutes' => $appointment->duration_minutes,
                    'price_cents' => $appointment->price_cents,
                    'position' => 0,
                    'created_at' => $appointment->created_at ?? now(),
                    'updated_at' => $appointment->updated_at ?? now(),
                ])->all();

                DB::table('appointment_service')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_service');
    }
};
