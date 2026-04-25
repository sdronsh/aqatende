<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('categoria_financeira_id')->nullable()->constrained('categorias_financeiras')->nullOnDelete();
            $table->string('fornecedor')->nullable();
            $table->string('descricao');
            $table->unsignedBigInteger('valor_cents');
            $table->date('data_emissao')->nullable();
            $table->date('data_vencimento');
            $table->dateTime('pago_em')->nullable();
            $table->string('status', 20)->default('aberto');
            $table->string('forma_pagamento', 20)->nullable();
            $table->string('centro_custo')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'status']);
            $table->index(['clinic_id', 'data_vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_pagar');
    }
};
