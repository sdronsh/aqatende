<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecord extends Model
{
    protected $fillable = [
        'appointment_id',
        'professional_id',
        'patient_id',
        'unit_id',
        'created_by',
        'notes',
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
        'plano_terapeutico',
        'conduta_medica',
        'prescricao',
        'orientacoes_paciente',
        'solicita_exames_laboratoriais',
        'solicita_exames_imagem',
        'solicita_encaminhamento',
        'solicita_atestado',
        'solicita_receita',
        'documentos_gerados',
        'documentos_gerados_detalhes',
        'anexos_documentos',
        'anexos_exames',
        'anexos_receitas',
        'anexos_laudos',
        'anexos_atestados',
        'medico_crm',
        'uf_crm',
        'assinatura_digital',
        'data_finalizacao',
        'termo_lgpd_aceito',
    ];

    protected $casts = [
        'data_atendimento' => 'date',
        'hora_inicio' => 'string',
        'hora_fim' => 'string',
        'peso_kg' => 'decimal:2',
        'altura_cm' => 'decimal:2',
        'imc' => 'decimal:2',
        'temperatura' => 'decimal:1',
        'cid_secundario' => 'array',
        'documentos_gerados' => 'array',
        'documentos_gerados_detalhes' => 'array',
        'anexos_documentos' => 'array',
        'anexos_exames' => 'array',
        'anexos_receitas' => 'array',
        'anexos_laudos' => 'array',
        'anexos_atestados' => 'array',
        'data_finalizacao' => 'datetime',
        'termo_lgpd_aceito' => 'bool',
        'solicita_exames_laboratoriais' => 'bool',
        'solicita_exames_imagem' => 'bool',
        'solicita_encaminhamento' => 'bool',
        'solicita_atestado' => 'bool',
        'solicita_receita' => 'bool',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
