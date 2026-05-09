<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_booking_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token', 80)->unique();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_booking_links');
    }
};
