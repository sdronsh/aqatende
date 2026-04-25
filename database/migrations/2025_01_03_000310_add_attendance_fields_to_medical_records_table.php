<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('patient_id')->constrained()->nullOnDelete();
            $table->string('tipo_atendimento', 30)->nullable()->after('notes');
            $table->date('data_atendimento')->nullable()->after('tipo_atendimento');
            $table->time('hora_inicio')->nullable()->after('data_atendimento');
            $table->time('hora_fim')->nullable()->after('hora_inicio');
            $table->string('status_atendimento', 20)->default('em_andamento')->after('hora_fim');

            $table->text('queixa_principal')->nullable()->after('status_atendimento');
            $table->longText('historia_doenca_atual')->nullable()->after('queixa_principal');

            $table->text('antecedentes_doencas_cronicas')->nullable()->after('historia_doenca_atual');
            $table->text('antecedentes_cirurgias')->nullable()->after('antecedentes_doencas_cronicas');
            $table->text('antecedentes_alergias')->nullable()->after('antecedentes_cirurgias');
            $table->text('antecedentes_medicamentos')->nullable()->after('antecedentes_alergias');

            $table->decimal('peso_kg', 6, 2)->nullable()->after('antecedentes_medicamentos');
            $table->decimal('altura_cm', 6, 2)->nullable()->after('peso_kg');
            $table->decimal('imc', 6, 2)->nullable()->after('altura_cm');
            $table->string('pressao_arterial', 20)->nullable()->after('imc');
            $table->unsignedSmallInteger('frequencia_cardiaca')->nullable()->after('pressao_arterial');
            $table->unsignedSmallInteger('frequencia_respiratoria')->nullable()->after('frequencia_cardiaca');
            $table->decimal('temperatura', 4, 1)->nullable()->after('frequencia_respiratoria');
            $table->unsignedSmallInteger('saturacao_o2')->nullable()->after('temperatura');

            $table->longText('exame_fisico_geral')->nullable()->after('saturacao_o2');
            $table->longText('exame_cardiovascular')->nullable()->after('exame_fisico_geral');
            $table->longText('exame_respiratorio')->nullable()->after('exame_cardiovascular');
            $table->longText('exame_abdome')->nullable()->after('exame_respiratorio');
            $table->longText('exame_neurologico')->nullable()->after('exame_abdome');
            $table->longText('exame_outros')->nullable()->after('exame_neurologico');

            $table->string('cid_principal', 20)->nullable()->after('exame_outros');
            $table->json('cid_secundario')->nullable()->after('cid_principal');
            $table->longText('descricao_diagnostico')->nullable()->after('cid_secundario');

            $table->longText('conduta_medica')->nullable()->after('descricao_diagnostico');
            $table->longText('prescricao')->nullable()->after('conduta_medica');
            $table->longText('orientacoes_paciente')->nullable()->after('prescricao');

            $table->boolean('solicita_exames_laboratoriais')->default(false)->after('orientacoes_paciente');
            $table->boolean('solicita_exames_imagem')->default(false)->after('solicita_exames_laboratoriais');
            $table->boolean('solicita_encaminhamento')->default(false)->after('solicita_exames_imagem');
            $table->boolean('solicita_atestado')->default(false)->after('solicita_encaminhamento');
            $table->boolean('solicita_receita')->default(false)->after('solicita_atestado');

            $table->json('documentos_gerados')->nullable()->after('solicita_receita');

            $table->string('medico_crm', 30)->nullable()->after('documentos_gerados');
            $table->string('uf_crm', 5)->nullable()->after('medico_crm');
            $table->string('assinatura_digital')->nullable()->after('uf_crm');
            $table->dateTime('data_finalizacao')->nullable()->after('assinatura_digital');
            $table->boolean('termo_lgpd_aceito')->default(false)->after('data_finalizacao');
        });
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn([
                'tipo_atendimento',
                'data_atendimento',
                'hora_inicio',
                'hora_fim',
                'status_atendimento',
                'queixa_principal',
                'historia_doenca_atual',
                'antecedentes_doencas_cronicas',
                'antecedentes_cirurgias',
                'antecedentes_alergias',
                'antecedentes_medicamentos',
                'peso_kg',
                'altura_cm',
                'imc',
                'pressao_arterial',
                'frequencia_cardiaca',
                'frequencia_respiratoria',
                'temperatura',
                'saturacao_o2',
                'exame_fisico_geral',
                'exame_cardiovascular',
                'exame_respiratorio',
                'exame_abdome',
                'exame_neurologico',
                'exame_outros',
                'cid_principal',
                'cid_secundario',
                'descricao_diagnostico',
                'conduta_medica',
                'prescricao',
                'orientacoes_paciente',
                'solicita_exames_laboratoriais',
                'solicita_exames_imagem',
                'solicita_encaminhamento',
                'solicita_atestado',
                'solicita_receita',
                'documentos_gerados',
                'medico_crm',
                'uf_crm',
                'assinatura_digital',
                'data_finalizacao',
                'termo_lgpd_aceito',
            ]);
        });
    }
};
