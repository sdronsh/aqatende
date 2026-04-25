<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fluxo_caixa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conta_bancaria_id')->nullable()->constrained('contas_bancarias')->nullOnDelete();
            $table->foreignId('categoria_financeira_id')->nullable()->constrained('categorias_financeiras')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tipo', 10);
            $table->string('origem', 30)->nullable();
            $table->unsignedBigInteger('origem_id')->nullable();
            $table->string('descricao')->nullable();
            $table->unsignedBigInteger('valor_cents');
            $table->dateTime('data_movimento');
            $table->string('forma_pagamento', 20)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'data_movimento']);
            $table->index(['clinic_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fluxo_caixa');
    }
};
