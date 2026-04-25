@php
    use Illuminate\Support\Carbon;
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $textarea = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $select = $input;

    $patient = $appointment->patient;
    $professional = $appointment->professional;
    $age = $patient?->birthdate ? Carbon::parse($patient->birthdate)->age : null;
    $readOnly = ($record->status_atendimento ?? '') === 'finalizado' || $record->data_finalizacao;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-800">Atendimento</h2>
            <a class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ route('attendance.agenda') }}">Voltar</a>
        </div>
    </x-slot>

    <div class="space-y-4">
        <div class="sticky top-4 z-10 rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm">
            <div class="grid gap-3 md:grid-cols-4">
                <div>
                    <div class="text-xs uppercase text-gray-400">Cliente</div>
                    <div class="text-sm font-semibold text-gray-800">{{ $patient?->full_name ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400">Idade</div>
                    <div class="text-sm font-semibold text-gray-800">{{ $age !== null ? $age . ' anos' : '-' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400">Sexo</div>
                    <div class="text-sm font-semibold text-gray-800">{{ $patient?->gender ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400">Prontuario</div>
                    <div class="text-sm font-semibold text-gray-800">{{ $patient?->medical_record_number ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400">Convenio</div>
                    <div class="text-sm font-semibold text-gray-800">{{ $patient?->insurance_name ?? $patient?->insurance_plan_name ?? 'Particular' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400">Medico</div>
                    <div class="text-sm font-semibold text-gray-800">{{ $professional?->display_name ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400">Agendamento</div>
                    <div class="text-sm font-semibold text-gray-800">{{ $appointment->scheduled_at?->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>

        @if ($readOnly)
            @php
                $canReopen = auth()->user()?->professional
                    && $appointment->professional_id === auth()->user()?->professional?->id;
            @endphp
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <span>Atendimento finalizado. Edicao bloqueada.</span>
                @if ($canReopen)
                    <form method="POST" action="{{ route('attendance.record.reopen', $appointment) }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100" onclick="return confirm('Reabrir este atendimento para edicao?')">
                            Reabrir atendimento
                        </button>
                    </form>
                @endif
            </div>
        @endif

        <div id="attendance-form-notice" class="hidden rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800"></div>

        <form id="attendance-record-form" method="POST" action="{{ route('attendance.record.update', $appointment) }}" class="space-y-4" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
                <div class="flex flex-wrap gap-2 border-b border-gray-200 px-4 py-3">
                    <button type="button" class="tab-button rounded-lg bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-600" data-tab="geral">Geral</button>
                    <button type="button" class="tab-button rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50" data-tab="anamnese">Anamnese</button>
                    <button type="button" class="tab-button rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50" data-tab="sinais">Sinais vitais</button>
                    <button type="button" class="tab-button rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50" data-tab="exame">Exame fisico</button>
                    <button type="button" class="tab-button rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50" data-tab="diagnostico">Diagnostico</button>
                    <button type="button" class="tab-button rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50" data-tab="conduta">Conduta</button>
                    <button type="button" class="tab-button rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50" data-tab="solicitacoes">Solicitacoes</button>
                    <button type="button" class="tab-button rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50" data-tab="legal">Validacao</button>
                    <button type="button" class="tab-button rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50" data-tab="documentos">Documentos</button>
                </div>

                <div class="p-4">
                    <div class="tab-panel space-y-4" data-tab-panel="geral">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="tipo_atendimento">Tipo atendimento</label>
                                <select class="{{ $select }}" id="tipo_atendimento" name="tipo_atendimento" @disabled($readOnly)>
                                    @php $tipo = old('tipo_atendimento', $record->tipo_atendimento ?? 'consulta'); @endphp
                                    <option value="consulta" @selected($tipo === 'consulta')>Consulta</option>
                                    <option value="retorno" @selected($tipo === 'retorno')>Retorno</option>
                                    <option value="urgencia" @selected($tipo === 'urgencia')>Urgencia</option>
                                    <option value="telemedicina" @selected($tipo === 'telemedicina')>Telemedicina</option>
                                </select>
                                <x-input-error class="mt-1" :messages="$errors->get('tipo_atendimento')" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="data_atendimento">Data atendimento</label>
                                <input class="{{ $input }}" type="date" id="data_atendimento" name="data_atendimento" value="{{ old('data_atendimento', optional($record->data_atendimento)->format('Y-m-d') ?? $appointment->scheduled_at?->toDateString()) }}" @disabled($readOnly) />
                                <x-input-error class="mt-1" :messages="$errors->get('data_atendimento')" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="status_atendimento">Status</label>
                                @php $status = old('status_atendimento', $record->status_atendimento ?? 'em_andamento'); @endphp
                                <select class="{{ $select }}" id="status_atendimento" name="status_atendimento" @disabled($readOnly)>
                                    <option value="em_andamento" @selected($status === 'em_andamento')>Em andamento</option>
                                    <option value="finalizado" @selected($status === 'finalizado')>Finalizado</option>
                                    <option value="cancelado" @selected($status === 'cancelado')>Cancelado</option>
                                </select>
                                <x-input-error class="mt-1" :messages="$errors->get('status_atendimento')" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="hora_inicio">Hora inicio</label>
                                <input class="{{ $input }}" type="time" id="hora_inicio" name="hora_inicio" value="{{ old('hora_inicio', $record->hora_inicio ? substr($record->hora_inicio, 0, 5) : $appointment->scheduled_at?->format('H:i')) }}" @disabled($readOnly) />
                                <x-input-error class="mt-1" :messages="$errors->get('hora_inicio')" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="hora_fim">Hora fim</label>
                                <input class="{{ $input }}" type="time" id="hora_fim" name="hora_fim" value="{{ old('hora_fim', $record->hora_fim ? substr($record->hora_fim, 0, 5) : $appointment->ends_at?->format('H:i')) }}" @disabled($readOnly) />
                                <x-input-error class="mt-1" :messages="$errors->get('hora_fim')" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="unit_id">Local atendimento</label>
                                <select class="{{ $select }}" id="unit_id" name="unit_id" @disabled($readOnly)>
                                    <option value="">Selecione</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}" @selected((int) old('unit_id', $record->unit_id ?? $appointment->unit_id) === (int) $unit->id)>
                                            {{ $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-1" :messages="$errors->get('unit_id')" />
                            </div>
                        </div>
                    </div>

                    <div class="tab-panel hidden space-y-4" data-tab-panel="anamnese">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700" for="queixa_principal">Queixa principal</label>
                            <textarea class="{{ $textarea }}" id="queixa_principal" name="queixa_principal" rows="3" @disabled($readOnly)>{{ old('queixa_principal', $record->queixa_principal) }}</textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700" for="historia_doenca_atual">Historia da doenca atual</label>
                            <textarea class="{{ $textarea }}" id="historia_doenca_atual" name="historia_doenca_atual" rows="4" @disabled($readOnly)>{{ old('historia_doenca_atual', $record->historia_doenca_atual) }}</textarea>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="antecedentes_doencas_cronicas">Doencas cronicas</label>
                                <textarea class="{{ $textarea }}" id="antecedentes_doencas_cronicas" name="antecedentes_doencas_cronicas" rows="3" @disabled($readOnly)>{{ old('antecedentes_doencas_cronicas', $record->antecedentes_doencas_cronicas) }}</textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="antecedentes_cirurgias">Cirurgias previas</label>
                                <textarea class="{{ $textarea }}" id="antecedentes_cirurgias" name="antecedentes_cirurgias" rows="3" @disabled($readOnly)>{{ old('antecedentes_cirurgias', $record->antecedentes_cirurgias) }}</textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="antecedentes_alergias">Alergias</label>
                                <textarea class="{{ $textarea }}" id="antecedentes_alergias" name="antecedentes_alergias" rows="3" @disabled($readOnly)>{{ old('antecedentes_alergias', $record->antecedentes_alergias) }}</textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="antecedentes_medicamentos">Medicamentos em uso</label>
                                <textarea class="{{ $textarea }}" id="antecedentes_medicamentos" name="antecedentes_medicamentos" rows="3" @disabled($readOnly)>{{ old('antecedentes_medicamentos', $record->antecedentes_medicamentos) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="tab-panel hidden space-y-4" data-tab-panel="sinais">
                        <div class="grid gap-4 md:grid-cols-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="peso_kg">Peso (kg)</label>
                                <input class="{{ $input }}" type="number" step="0.01" id="peso_kg" name="peso_kg" value="{{ old('peso_kg', $record->peso_kg) }}" @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="altura_cm">Altura (cm)</label>
                                <input class="{{ $input }}" type="number" step="0.01" id="altura_cm" name="altura_cm" value="{{ old('altura_cm', $record->altura_cm) }}" @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="imc">IMC</label>
                                <input class="{{ $input }}" type="number" step="0.01" id="imc" name="imc" value="{{ old('imc', $record->imc) }}" readonly />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="pressao_arterial">Pressao arterial</label>
                                <input class="{{ $input }}" type="text" id="pressao_arterial" name="pressao_arterial" value="{{ old('pressao_arterial', $record->pressao_arterial) }}" @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="frequencia_cardiaca">Freq. cardiaca</label>
                                <input class="{{ $input }}" type="number" id="frequencia_cardiaca" name="frequencia_cardiaca" value="{{ old('frequencia_cardiaca', $record->frequencia_cardiaca) }}" @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="frequencia_respiratoria">Freq. respiratoria</label>
                                <input class="{{ $input }}" type="number" id="frequencia_respiratoria" name="frequencia_respiratoria" value="{{ old('frequencia_respiratoria', $record->frequencia_respiratoria) }}" @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="temperatura">Temperatura</label>
                                <input class="{{ $input }}" type="number" step="0.1" id="temperatura" name="temperatura" value="{{ old('temperatura', $record->temperatura) }}" @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="saturacao_o2">Saturacao O2</label>
                                <input class="{{ $input }}" type="number" id="saturacao_o2" name="saturacao_o2" value="{{ old('saturacao_o2', $record->saturacao_o2) }}" @disabled($readOnly) />
                            </div>
                        </div>
                    </div>

                    <div class="tab-panel hidden space-y-4" data-tab-panel="exame">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700" for="exame_fisico_geral">Exame fisico geral</label>
                            <textarea class="{{ $textarea }}" id="exame_fisico_geral" name="exame_fisico_geral" rows="3" @disabled($readOnly)>{{ old('exame_fisico_geral', $record->exame_fisico_geral) }}</textarea>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="exame_cardiovascular">Cardiovascular</label>
                                <textarea class="{{ $textarea }}" id="exame_cardiovascular" name="exame_cardiovascular" rows="3" @disabled($readOnly)>{{ old('exame_cardiovascular', $record->exame_cardiovascular) }}</textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="exame_respiratorio">Respiratorio</label>
                                <textarea class="{{ $textarea }}" id="exame_respiratorio" name="exame_respiratorio" rows="3" @disabled($readOnly)>{{ old('exame_respiratorio', $record->exame_respiratorio) }}</textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="exame_abdome">Abdome</label>
                                <textarea class="{{ $textarea }}" id="exame_abdome" name="exame_abdome" rows="3" @disabled($readOnly)>{{ old('exame_abdome', $record->exame_abdome) }}</textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="exame_neurologico">Neurologico</label>
                                <textarea class="{{ $textarea }}" id="exame_neurologico" name="exame_neurologico" rows="3" @disabled($readOnly)>{{ old('exame_neurologico', $record->exame_neurologico) }}</textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="exame_outros">Outros</label>
                                <textarea class="{{ $textarea }}" id="exame_outros" name="exame_outros" rows="3" @disabled($readOnly)>{{ old('exame_outros', $record->exame_outros) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="tab-panel hidden space-y-4" data-tab-panel="diagnostico">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="cid_principal">CID principal</label>
                                <input class="{{ $input }}" type="text" id="cid_principal" name="cid_principal" value="{{ old('cid_principal', $record->cid_principal) }}" @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="cid_secundario">CID secundario</label>
                                @php
                                    $cidSec = old('cid_secundario', is_array($record->cid_secundario) ? implode(', ', $record->cid_secundario) : '');
                                @endphp
                                <input class="{{ $input }}" type="text" id="cid_secundario" name="cid_secundario" value="{{ $cidSec }}" placeholder="Ex: A10, B20" @disabled($readOnly) />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700" for="descricao_diagnostico">Descricao diagnostico</label>
                            <textarea class="{{ $textarea }}" id="descricao_diagnostico" name="descricao_diagnostico" rows="3" @disabled($readOnly)>{{ old('descricao_diagnostico', $record->descricao_diagnostico) }}</textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700" for="plano_terapeutico">Plano terapeutico</label>
                            <textarea class="{{ $textarea }}" id="plano_terapeutico" name="plano_terapeutico" rows="3" @disabled($readOnly)>{{ old('plano_terapeutico', $record->plano_terapeutico) }}</textarea>
                        </div>
                    </div>

                    <div class="tab-panel hidden space-y-4" data-tab-panel="conduta">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700" for="conduta_medica">Conduta medica</label>
                            <textarea class="{{ $textarea }}" id="conduta_medica" name="conduta_medica" rows="3" @disabled($readOnly)>{{ old('conduta_medica', $record->conduta_medica) }}</textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700" for="prescricao">Prescricao</label>
                            <textarea class="{{ $textarea }}" id="prescricao" name="prescricao" rows="3" @disabled($readOnly)>{{ old('prescricao', $record->prescricao) }}</textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700" for="orientacoes_paciente">Orientacoes ao cliente</label>
                            <textarea class="{{ $textarea }}" id="orientacoes_paciente" name="orientacoes_paciente" rows="3" @disabled($readOnly)>{{ old('orientacoes_paciente', $record->orientacoes_paciente) }}</textarea>
                        </div>
                    </div>

                    <div class="tab-panel hidden space-y-4" data-tab-panel="solicitacoes">
                        <div class="grid gap-3 md:grid-cols-2">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-500" name="solicita_exames_laboratoriais" value="1" @checked(old('solicita_exames_laboratoriais', $record->solicita_exames_laboratoriais)) @disabled($readOnly) />
                                Exames laboratoriais
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-500" name="solicita_exames_imagem" value="1" @checked(old('solicita_exames_imagem', $record->solicita_exames_imagem)) @disabled($readOnly) />
                                Exames de imagem
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-500" name="solicita_encaminhamento" value="1" @checked(old('solicita_encaminhamento', $record->solicita_encaminhamento)) @disabled($readOnly) />
                                Encaminhamentos
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-500" name="solicita_atestado" value="1" @checked(old('solicita_atestado', $record->solicita_atestado)) @disabled($readOnly) />
                                Atestado medico
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-500" name="solicita_receita" value="1" @checked(old('solicita_receita', $record->solicita_receita)) @disabled($readOnly) />
                                Receita
                            </label>
                        </div>
                    </div>

                    <div class="tab-panel hidden space-y-4" data-tab-panel="documentos">
                        <input type="hidden" id="documentos_gerados" name="documentos_gerados" value="{{ old('documentos_gerados', $record->documentos_gerados ? json_encode($record->documentos_gerados) : '') }}" />
                    <input type="hidden" id="documentos_gerados_detalhes" name="documentos_gerados_detalhes" value="{{ old('documentos_gerados_detalhes', $record->documentos_gerados_detalhes ? json_encode($record->documentos_gerados_detalhes) : '') }}" />
                    <input type="hidden" id="documentos_gerados_detalhes_touched" name="documentos_gerados_detalhes_touched" value="{{ old('documentos_gerados_detalhes_touched', $record->documentos_gerados_detalhes !== null ? '1' : '') }}" />
                    <input type="hidden" id="save_documents_only" name="save_documents_only" value="0" />
                        <div>
                            <div class="text-sm font-semibold text-gray-800">Documentos gerados</div>
                            <div id="documentos_preview" class="mt-2 grid gap-3 md:grid-cols-2"></div>
                            <div id="documentos_vazio" class="mt-2 text-xs text-gray-500">Nenhum documento selecionado.</div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="attachments_documents">Anexar documentos</label>
                                <input class="{{ $input }}" type="file" id="attachments_documents" name="attachments_documents[]" multiple @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="attachments_exams">Anexar exames</label>
                                <input class="{{ $input }}" type="file" id="attachments_exams" name="attachments_exams[]" multiple @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="attachments_prescriptions">Anexar receitas</label>
                                <input class="{{ $input }}" type="file" id="attachments_prescriptions" name="attachments_prescriptions[]" multiple @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="attachments_reports">Anexar laudos</label>
                                <input class="{{ $input }}" type="file" id="attachments_reports" name="attachments_reports[]" multiple @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="attachments_certificates">Anexar atestados</label>
                                <input class="{{ $input }}" type="file" id="attachments_certificates" name="attachments_certificates[]" multiple @disabled($readOnly) />
                            </div>
                        </div>
                    </div>

                    <div class="tab-panel hidden space-y-4" data-tab-panel="legal">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="medico_crm">CRM</label>
                                <input class="{{ $input }}" type="text" id="medico_crm" name="medico_crm" value="{{ old('medico_crm', $record->medico_crm ?? $professional?->crm_number) }}" @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="uf_crm">UF CRM</label>
                                <input class="{{ $input }}" type="text" id="uf_crm" name="uf_crm" value="{{ old('uf_crm', $record->uf_crm ?? $professional?->crm_state) }}" @disabled($readOnly) />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="assinatura_digital">Assinatura digital</label>
                                <input class="{{ $input }}" type="text" id="assinatura_digital" name="assinatura_digital" value="{{ old('assinatura_digital', $record->assinatura_digital) }}" @disabled($readOnly) />
                            </div>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-500" name="termo_lgpd_aceito" value="1" @checked(old('termo_lgpd_aceito', $record->termo_lgpd_aceito)) @disabled($readOnly) />
                            Termo LGPD aceito
                        </label>
                        <x-input-error class="mt-1" :messages="$errors->get('termo_lgpd_aceito')" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600" @disabled($readOnly)>
                    Salvar atendimento
                </button>
            </div>
        </form>
    </div>

    <dialog id="documento-modal" class="w-full max-w-5xl rounded-xl border border-gray-200 p-0 shadow-theme-lg">
        <div class="flex flex-col gap-4 p-5">
            <div class="flex items-center justify-between">
                <h3 id="documento-modal-title" class="text-lg font-semibold text-gray-800">Documento</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" data-close-documento>&times;</button>
            </div>
            <div class="grid gap-4 md:grid-cols-[1fr,1fr]">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="documento-modal-text">Conteudo do documento</label>
                    <textarea id="documento-modal-text" class="{{ $textarea }}" rows="12"></textarea>
                </div>
                <div>
                    <div class="mb-1 text-sm font-medium text-gray-700">Pre-visualizacao</div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-3">
                            <img id="documento-preview-logo" class="h-10 w-auto object-contain hidden" alt="Logo" />
                            <img id="documento-preview-header-image" class="h-16 w-auto object-contain hidden" alt="Cabecalho" />
                        </div>
                        <div id="documento-preview-header" class="mt-3 text-sm text-gray-800"></div>
                        <div id="documento-preview-body" class="mt-4 text-sm text-gray-800"></div>
                        <div id="documento-preview-footer" class="mt-6 border-t border-gray-200 pt-3 text-xs text-gray-600"></div>
                        <div class="mt-3 flex justify-end">
                            <img id="documento-preview-footer-image" class="h-10 w-auto object-contain hidden" alt="Rodape" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" data-close-documento>Fechar</button>
                <button type="button" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" data-print-documento>Imprimir / PDF</button>
                <button type="button" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600" data-save-documento>Salvar conteudo</button>
            </div>
        </div>
    </dialog>
</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const buttons = document.querySelectorAll('.tab-button');
        const panels = document.querySelectorAll('[data-tab-panel]');
        const attendanceForm = document.getElementById('attendance-record-form');
        const formNotice = document.getElementById('attendance-form-notice');
        const activateTab = (tab) => {
            if (!tab) return;
            buttons.forEach((b) => b.classList.remove('bg-brand-50', 'text-brand-600'));
            buttons.forEach((b) => b.classList.add('text-gray-600'));
            const activeButton = Array.from(buttons).find((b) => b.dataset.tab === tab);
            if (activeButton) {
                activeButton.classList.add('bg-brand-50', 'text-brand-600');
            }
            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.tabPanel !== tab);
            });
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                activateTab(button.dataset.tab);
            });
        });

        const errorFields = @json(array_keys($errors->toArray()));
        if (errorFields.length) {
            const fieldToTab = {
                tipo_atendimento: 'geral',
                data_atendimento: 'geral',
                hora_inicio: 'geral',
                hora_fim: 'geral',
                unit_id: 'geral',
                status_atendimento: 'geral',
                queixa_principal: 'anamnese',
                historia_doenca_atual: 'anamnese',
                antecedentes_doencas_cronicas: 'anamnese',
                antecedentes_cirurgias: 'anamnese',
                antecedentes_alergias: 'anamnese',
                antecedentes_medicamentos: 'anamnese',
                peso_kg: 'sinais',
                altura_cm: 'sinais',
                pressao_arterial: 'sinais',
                frequencia_cardiaca: 'sinais',
                frequencia_respiratoria: 'sinais',
                temperatura: 'sinais',
                saturacao_o2: 'sinais',
                exame_fisico_geral: 'exame',
                exame_cardiovascular: 'exame',
                exame_respiratorio: 'exame',
                exame_abdome: 'exame',
                exame_neurologico: 'exame',
                exame_outros: 'exame',
                cid_principal: 'diagnostico',
                cid_secundario: 'diagnostico',
                descricao_diagnostico: 'diagnostico',
                plano_terapeutico: 'diagnostico',
                conduta_medica: 'conduta',
                prescricao: 'conduta',
                orientacoes_paciente: 'conduta',
                solicita_exames_laboratoriais: 'solicitacoes',
                solicita_exames_imagem: 'solicitacoes',
                solicita_encaminhamento: 'solicitacoes',
                solicita_atestado: 'solicitacoes',
                solicita_receita: 'solicitacoes',
                documentos_gerados: 'documentos',
                documentos_gerados_detalhes: 'documentos',
                medico_crm: 'legal',
                uf_crm: 'legal',
                assinatura_digital: 'legal',
                termo_lgpd_aceito: 'legal',
            };

            const targetTab = errorFields.map((field) => fieldToTab[field]).find(Boolean);
            if (targetTab) {
                activateTab(targetTab);
                const targetPanel = document.querySelector(`[data-tab-panel="${targetTab}"]`);
                targetPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        const showFormNotice = (message) => {
            if (!formNotice) return;
            formNotice.textContent = message;
            formNotice.classList.remove('hidden');
        };

        const weightInput = document.getElementById('peso_kg');
        const heightInput = document.getElementById('altura_cm');
        const imcInput = document.getElementById('imc');

        const updateImc = () => {
            if (!weightInput || !heightInput || !imcInput) return;
            const weight = parseFloat(weightInput.value.replace(',', '.'));
            const height = parseFloat(heightInput.value.replace(',', '.')) / 100;
            if (!weight || !height) {
                imcInput.value = '';
                return;
            }
            const imc = weight / (height * height);
            imcInput.value = imc.toFixed(2);
        };

        if (weightInput && heightInput) {
            weightInput.addEventListener('input', updateImc);
            heightInput.addEventListener('input', updateImc);
            updateImc();
        }

        const previewContainer = document.getElementById('documentos_preview');
        const previewEmpty = document.getElementById('documentos_vazio');
        const hiddenDocs = document.getElementById('documentos_gerados');
        const hiddenDetails = document.getElementById('documentos_gerados_detalhes');
        const hiddenDetailsTouched = document.getElementById('documentos_gerados_detalhes_touched');
        const initialDetailsValue = hiddenDetails?.value?.trim();
        let detailsTouched = hiddenDetailsTouched?.value === '1' || Boolean(initialDetailsValue);
        const docModal = document.getElementById('documento-modal');
        const docModalTitle = document.getElementById('documento-modal-title');
        const docModalText = document.getElementById('documento-modal-text');
        const docSaveButton = docModal?.querySelector('[data-save-documento]');
        const docPrintButton = docModal?.querySelector('[data-print-documento]');
        const saveDocumentsOnlyInput = document.getElementById('save_documents_only');
        const isReadOnly = {{ $readOnly ? 'true' : 'false' }};
        const templates = @json($documentTemplates);
        const companyLogoUrl = @json($companyLogoUrl);

        const baseInfo = {
            paciente: @json($patient?->full_name ?? '-'),
            idade: @json($patientAge ?? '-'),
            convenio: @json($patient?->insurance_name ?? '-'),
            medico: @json($professional?->display_name ?? '-'),
            especialidade: @json(optional($professional?->specialties?->first())->name ?? '-'),
            data: @json($appointment->scheduled_at?->format('d/m/Y') ?? '-'),
            hora: @json($appointment->scheduled_at?->format('H:i') ?? '-'),
        };
        const checkboxes = [
            { id: 'solicita_exames_laboratoriais', label: 'Pedido de exames laboratoriais' },
            { id: 'solicita_exames_imagem', label: 'Pedido de exames de imagem' },
            { id: 'solicita_encaminhamento', label: 'Encaminhamento' },
            { id: 'solicita_atestado', label: 'Atestado medico' },
            { id: 'solicita_receita', label: 'Receita' },
        ];
        const docTypeByLabel = {
            'Pedido de exames laboratoriais': 'pedido_exames',
            'Pedido de exames de imagem': 'pedido_exames',
            'Encaminhamento': 'encaminhamento',
            'Atestado medico': 'atestado',
            'Receita': 'receita',
        };

        const parseDetails = () => {
            if (!hiddenDetails || !hiddenDetails.value) return {};
            try {
                let parsed = JSON.parse(hiddenDetails.value);
                if (typeof parsed === 'string') {
                    try {
                        parsed = JSON.parse(parsed);
                    } catch (error) {
                        return {};
                    }
                }
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                return {};
            }
        };

        let detailsMap = parseDetails();
        let activeDocLabel = null;

        const buildDefaultContent = (label) => {
            const header = `${label}\n\n`;
            const info = [
                `Cliente: ${baseInfo.paciente}`,
                `Idade: ${baseInfo.idade}`,
                `Convenio: ${baseInfo.convenio}`,
                `Medico: ${baseInfo.medico}`,
                `Especialidade: ${baseInfo.especialidade}`,
                `Data/Hora: ${baseInfo.data} ${baseInfo.hora}`,
            ].join('\n');

            return `${header}${info}\n\nDescricao:\n`;
        };

        const syncDetails = (selected) => {
            if (!hiddenDetails) return;
            const filtered = {};
            selected.forEach((label) => {
                if (Object.prototype.hasOwnProperty.call(detailsMap, label)) {
                    filtered[label] = detailsMap[label];
                } else if (detailsTouched) {
                    filtered[label] = '';
                } else {
                    filtered[label] = buildDefaultContent(label);
                }
            });
            detailsMap = filtered;
            hiddenDetails.value = JSON.stringify(detailsMap);
        };

        const toAbsoluteUrl = (url) => {
            if (!url) return '';
            if (url.startsWith('http://') || url.startsWith('https://')) {
                return url;
            }
            if (url.startsWith('/')) {
                return `${window.location.origin}${url}`;
            }
            return url;
        };

        const mapPlaceholders = (html, content = '') => {
            const safeContent = escapeHtml(content || '').replace(/\n/g, '<br />');
            return (html || '')
                .replaceAll('@{{patient_name}}', baseInfo.paciente)
                .replaceAll('@{{patient_cpf}}', @json($patient?->cpf ?? '-'))
                .replaceAll('@{{patient_birthdate}}', @json(optional($patient?->birthdate)->format('d/m/Y') ?? '-'))
                .replaceAll('@{{doctor_name}}', baseInfo.medico)
                .replaceAll('@{{doctor_crm}}', @json($professional?->crm_state ? $professional->crm_state.' '.$professional->crm_number : '-'))
                .replaceAll('@{{appointment_date}}', `${baseInfo.data} ${baseInfo.hora}`)
                .replaceAll('@{{attendance_date}}', @json(optional($record->data_atendimento)->format('d/m/Y') ?? $appointment->scheduled_at?->format('d/m/Y') ?? '-'))
                .replaceAll('@{{document_body}}', safeContent);
        };

        const escapeHtml = (value) => {
            return (value || '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            }[char]));
        };

        const docPreviewHeader = document.getElementById('documento-preview-header');
        const docPreviewBody = document.getElementById('documento-preview-body');
        const docPreviewFooter = document.getElementById('documento-preview-footer');
        const docPreviewLogo = document.getElementById('documento-preview-logo');
        const docPreviewHeaderImage = document.getElementById('documento-preview-header-image');
        const docPreviewFooterImage = document.getElementById('documento-preview-footer-image');

        const refreshDocPreview = (label, content) => {
            const type = docTypeByLabel[label];
            const template = type ? templates[type] : null;
            const headerHtml = mapPlaceholders(template?.header_html || '', content);
            const bodyHtml = mapPlaceholders(template?.body_html || '', content);
            const footerHtml = mapPlaceholders(template?.footer_html || '', content);
            const hasBodyPlaceholder = (template?.body_html || '').includes('@{{document_body}}');

            if (docPreviewHeader) docPreviewHeader.innerHTML = headerHtml;
            if (docPreviewBody) {
                docPreviewBody.innerHTML = bodyHtml;
                if (!hasBodyPlaceholder && content) {
                    docPreviewBody.innerHTML += `<div class="mt-3 text-sm">${escapeHtml(content).replace(/\n/g, '<br />')}</div>`;
                }
            }
            if (docPreviewFooter) docPreviewFooter.innerHTML = footerHtml;

            if (docPreviewLogo) {
                if (companyLogoUrl) {
                    docPreviewLogo.src = toAbsoluteUrl(companyLogoUrl);
                    docPreviewLogo.classList.remove('hidden');
                } else {
                    docPreviewLogo.classList.add('hidden');
                }
            }
            if (docPreviewHeaderImage) {
                if (template?.header_image_url) {
                    docPreviewHeaderImage.src = toAbsoluteUrl(template.header_image_url);
                    docPreviewHeaderImage.classList.remove('hidden');
                } else {
                    docPreviewHeaderImage.classList.add('hidden');
                }
            }
            if (docPreviewFooterImage) {
                if (template?.footer_image_url) {
                    docPreviewFooterImage.src = toAbsoluteUrl(template.footer_image_url);
                    docPreviewFooterImage.classList.remove('hidden');
                } else {
                    docPreviewFooterImage.classList.add('hidden');
                }
            }
        };

        const openDocModal = (label) => {
            if (!docModal || !docModalTitle || !docModalText) return;
            if (Object.keys(detailsMap).length === 0 && hiddenDetails?.value) {
                detailsMap = parseDetails();
            }
            activeDocLabel = label;
            docModalTitle.textContent = label;
            docModalText.value = Object.prototype.hasOwnProperty.call(detailsMap, label)
                ? detailsMap[label]
                : (detailsTouched ? '' : buildDefaultContent(label));
            docModalText.disabled = isReadOnly;
            if (docSaveButton) {
                docSaveButton.classList.toggle('hidden', isReadOnly);
            }
            refreshDocPreview(label, docModalText.value);
            docModal.showModal();
        };

        const buildPreview = () => {
            if (!previewContainer || !hiddenDocs) return;
            if (Object.keys(detailsMap).length === 0 && hiddenDetails?.value) {
                detailsMap = parseDetails();
            }
            const selected = checkboxes
                .map((item) => ({ ...item, el: document.querySelector(`input[name="${item.id}"]`) }))
                .filter((item) => item.el && item.el.checked)
                .map((item) => item.label);

            previewContainer.innerHTML = '';
            if (selected.length === 0) {
                if (previewEmpty) previewEmpty.classList.remove('hidden');
                hiddenDocs.value = '';
                if (hiddenDetails) hiddenDetails.value = '';
                return;
            }
            if (previewEmpty) previewEmpty.classList.add('hidden');

            syncDetails(selected);

            selected.forEach((label) => {
                const card = document.createElement('button');
                card.type = 'button';
                card.className = 'w-full rounded-lg border border-gray-200 bg-gray-50 p-3 text-left text-xs text-gray-600 transition hover:border-brand-300 hover:bg-white';
                card.dataset.label = label;
                const rawSnippet = detailsMap[label] ?? '';
                const snippet = rawSnippet.trim()
                    ? rawSnippet.split('\n').slice(0, 3).join(' | ')
                    : 'Conteudo vazio';
                card.innerHTML = `<div class="text-sm font-semibold text-gray-800">${label}</div><div class="mt-1">${snippet}</div><div class="mt-2 text-[11px] text-brand-600">Editar documento</div>`;
                card.addEventListener('click', () => openDocModal(label));
                previewContainer.appendChild(card);
            });

            hiddenDocs.value = JSON.stringify(selected);
        };

        const hydrateSelectedDocs = () => {
            if (!hiddenDocs || !hiddenDocs.value) return;
            try {
                const selected = JSON.parse(hiddenDocs.value);
                if (!Array.isArray(selected)) return;
                selected.forEach((label) => {
                    const docCheckbox = checkboxes.find((item) => item.label === label);
                    if (!docCheckbox) return;
                    const el = document.querySelector(`input[name="${docCheckbox.id}"]`);
                    if (el) {
                        el.checked = true;
                    }
                });
            } catch (error) {
                // Ignore invalid JSON
            }
        };

        checkboxes.forEach((item) => {
            const el = document.querySelector(`input[name="${item.id}"]`);
            if (el) {
                el.addEventListener('change', buildPreview);
            }
        });

        if (docModal) {
            docModal.querySelectorAll('[data-close-documento]').forEach((button) => {
                button.addEventListener('click', () => docModal.close());
            });
            docModal.addEventListener('click', (event) => {
                if (event.target === docModal) {
                    docModal.close();
                }
            });
            docModal.addEventListener('close', () => {
                if (saveDocumentsOnlyInput) saveDocumentsOnlyInput.value = '0';
            });
        }

        if (docModalText) {
            docModalText.addEventListener('input', () => {
                if (!activeDocLabel) return;
                refreshDocPreview(activeDocLabel, docModalText.value);
            });
        }

        if (docSaveButton) {
            docSaveButton.addEventListener('click', () => {
                if (!activeDocLabel || !docModalText) return;
                detailsMap[activeDocLabel] = docModalText.value;
                if (hiddenDetails) {
                    hiddenDetails.value = JSON.stringify(detailsMap);
                }
                if (hiddenDetailsTouched) {
                    hiddenDetailsTouched.value = '1';
                }
                detailsTouched = true;
                buildPreview();
                if (docModal) docModal.close();
                if (isReadOnly) {
                    showFormNotice('Atendimento finalizado. Conteudo nao pode ser alterado.');
                    return;
                }

                const docCheckbox = checkboxes.find((item) => item.label === activeDocLabel);
                if (docCheckbox) {
                    const docCheckboxEl = document.querySelector(`input[name="${docCheckbox.id}"]`);
                    if (docCheckboxEl && !docCheckboxEl.checked) {
                        docCheckboxEl.checked = true;
                    }
                }

                if (saveDocumentsOnlyInput) saveDocumentsOnlyInput.value = '1';

                if (attendanceForm?.requestSubmit) {
                    attendanceForm.requestSubmit();
                } else if (attendanceForm) {
                    attendanceForm.submit();
                }
            });
        }

        if (docPrintButton) {
            docPrintButton.addEventListener('click', () => {
                if (!activeDocLabel || !docModalText) return;
                const content = docModalText.value.trim();
                const type = docTypeByLabel[activeDocLabel];
                const template = type ? templates[type] : null;
                const headerHtml = mapPlaceholders(template?.header_html || '', content);
                const bodyHtml = mapPlaceholders(template?.body_html || '', content);
                const footerHtml = mapPlaceholders(template?.footer_html || '', content);
                const hasBodyPlaceholder = (template?.body_html || '').includes('@{{document_body}}');
                const bodyContent = hasBodyPlaceholder
                    ? bodyHtml
                    : `${bodyHtml}${content ? `<div style="margin-top:12px;">${escapeHtml(content).replace(/\\n/g, '<br />')}</div>` : ''}`;

                const logoMarkup = companyLogoUrl
                    ? `<img src="${toAbsoluteUrl(companyLogoUrl)}" style="height:48px; max-width:180px; object-fit:contain;" />`
                    : '';
                const headerImg = template?.header_image_url
                    ? `<img src="${toAbsoluteUrl(template.header_image_url)}" style="height:64px; max-width:240px; object-fit:contain;" />`
                    : '';
                const footerImg = template?.footer_image_url
                    ? `<img src="${toAbsoluteUrl(template.footer_image_url)}" style="height:40px; max-width:200px; object-fit:contain;" />`
                    : '';
                const printable = window.open('', '_blank');
                if (!printable) return;
                printable.document.write(`
                    <html>
                        <head>
                            <title>${activeDocLabel}</title>
                            <style>
                                body { font-family: Arial, sans-serif; padding: 24px; color: #111827; }
                                h1 { font-size: 18px; margin: 0 0 16px; }
                                .header { display:flex; justify-content: space-between; gap:16px; align-items:flex-start; }
                                .footer { margin-top:24px; border-top:1px solid #E5E7EB; padding-top:12px; font-size:12px; color:#4B5563; }
                                .content { font-size: 13px; line-height: 1.5; }
                                .page { max-width: 680px; margin: 0 auto; padding: 24px; border: 1px solid #E5E7EB; }
                            </style>
                        </head>
                        <body>
                            <div class="page">
                                <div class="header">
                                    ${logoMarkup}
                                    ${headerImg}
                                </div>
                                <div class="content">
                                    ${headerHtml}
                                    ${bodyContent}
                                    <div class="footer">${footerHtml}</div>
                                    <div style="display:flex; justify-content:flex-end; margin-top:12px;">${footerImg}</div>
                                </div>
                            </div>
                        </body>
                    </html>
                `);
                printable.document.close();
                printable.focus();
                printable.print();
            });
        }

        hydrateSelectedDocs();
        buildPreview();
    });
</script>
