<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20);
            $table->string('bank_name')->nullable();
            $table->string('agency', 20)->nullable();
            $table->string('account_number', 30)->nullable();
            $table->string('pix_key')->nullable();
            $table->unsignedBigInteger('initial_balance_cents')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['clinic_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_bancarias');
    }
};
