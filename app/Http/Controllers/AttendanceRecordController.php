<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\CompanySetting;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceRecordController extends Controller
{
    public function edit(Request $request, Appointment $appointment): View|RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        $user = $request->user();
        if (! $companyId) {
            if ($user?->is_platform_admin) {
                return redirect()->route('admin.company-select');
            }
            abort(403);
        }

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
        if (! $clinicIds->contains($appointment->clinic_id)) {
            abort(403);
        }

        if (! $user->is_platform_admin && $user->professional && $appointment->professional_id !== $user->professional->id) {
            abort(403);
        }

        $record = MedicalRecord::firstOrNew(['appointment_id' => $appointment->id]);

        if (! $record->exists) {
            $record->fill([
                'professional_id' => $appointment->professional_id,
                'patient_id' => $appointment->patient_id,
                'unit_id' => $appointment->unit_id,
                'created_by' => $user->id,
                'tipo_atendimento' => $appointment->channel === 'teleconsulta' ? 'telemedicina' : 'consulta',
                'data_atendimento' => $appointment->scheduled_at?->toDateString(),
                'hora_inicio' => $appointment->scheduled_at?->format('H:i'),
                'hora_fim' => $appointment->ends_at?->format('H:i'),
                'status_atendimento' => 'em_andamento',
            ]);

            $this->fillFromPatient($record, $appointment->patient);
        }

        $logoPath = CompanySetting::where('company_id', $companyId)->where('key', 'logo_path')->value('value');

        return view('attendance/record', [
            'appointment' => $appointment->load(['patient', 'professional.specialties', 'service', 'unit', 'clinic']),
            'record' => $record,
            'units' => Unit::where('clinic_id', $appointment->clinic_id)->orderBy('name')->get(),
            'documentTemplates' => [],
            'companyLogoUrl' => $logoPath ? asset('storage/'.$logoPath) : null,
        ]);
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        $user = $request->user();
        if (! $companyId) {
            if ($user?->is_platform_admin) {
                return redirect()->route('admin.company-select');
            }
            abort(403);
        }

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
        if (! $clinicIds->contains($appointment->clinic_id)) {
            abort(403);
        }

        if (! $user->is_platform_admin && $user->professional && $appointment->professional_id !== $user->professional->id) {
            abort(403);
        }

        $record = MedicalRecord::firstOrNew(['appointment_id' => $appointment->id]);
        if ($record->exists && $this->isFinalized($record)) {
            return back()->withErrors(['status_atendimento' => 'Atendimento finalizado. Nao e possivel editar.']);
        }

        if ($request->boolean('save_documents_only')) {
            $this->storeDocumentOnly($request, $record, $appointment);
            return redirect()
                ->route('attendance.record.edit', $appointment)
                ->with('status', 'Documento atualizado.');
        }

        $data = $this->validateRecord($request);
        $data['hora_inicio'] = $this->normalizeTime($data['hora_inicio'] ?? null);
        $data['hora_fim'] = $this->normalizeTime($data['hora_fim'] ?? null);
        $data['cid_secundario'] = $this->encodeJsonOrNull($this->parseCidList($data['cid_secundario'] ?? ''));
        $rawDocs = $data['documentos_gerados'] ?? null;
        $rawDetails = $data['documentos_gerados_detalhes'] ?? null;
        $selectedDocs = $this->parseJsonList($rawDocs);
        $detailsMap = $this->parseJsonMap($rawDetails);
        if ($request->boolean('documentos_gerados_detalhes_touched')) {
            if ($rawDetails) {
                $decodedDetails = json_decode($rawDetails, true);
                if (is_array($decodedDetails)) {
                    $detailsMap = $decodedDetails;
                }
            }
            foreach ($selectedDocs as $label) {
                if (! array_key_exists($label, $detailsMap)) {
                    $detailsMap[$label] = '';
                }
            }
        }
        $data['documentos_gerados'] = $this->encodeJsonOrNull($selectedDocs);
        $data['documentos_gerados_detalhes'] = $this->encodeJsonOrNull($detailsMap);
        $data['solicita_exames_laboratoriais'] = $request->boolean('solicita_exames_laboratoriais');
        $data['solicita_exames_imagem'] = $request->boolean('solicita_exames_imagem');
        $data['solicita_encaminhamento'] = $request->boolean('solicita_encaminhamento');
        $data['solicita_atestado'] = $request->boolean('solicita_atestado');
        $data['solicita_receita'] = $request->boolean('solicita_receita');

        $data['professional_id'] = $appointment->professional_id;
        $data['patient_id'] = $appointment->patient_id;
        $data['unit_id'] = $data['unit_id'] ?? $appointment->unit_id;
        $data['created_by'] = $record->created_by ?? $user->id;

        $data['imc'] = $this->calculateImc($data['peso_kg'] ?? null, $data['altura_cm'] ?? null);

        if ($data['status_atendimento'] === 'finalizado') {
            $data['data_finalizacao'] = $record->data_finalizacao ?? now();
        }

        $data = $this->encodeArrayValues($data);
        $this->debugAttendancePayload($data);
        $record->fill($data);
        $record->save();

        $record->fill($this->storeAttachments($request, $record, $appointment));
        $record->save();

        return redirect()->route('attendance.record.edit', $appointment)->with('status', 'Atendimento atualizado.');
    }

    public function reopen(Request $request, Appointment $appointment): RedirectResponse
    {
        $companyId = $request->session()->get('active_company_id');
        if (! $companyId) {
            abort(403);
        }

        $clinicIds = Clinic::where('company_id', $companyId)->pluck('id');
        if (! $clinicIds->contains($appointment->clinic_id)) {
            abort(403);
        }

        $user = $request->user();
        if (! $user?->professional || $appointment->professional_id !== $user->professional->id) {
            abort(403);
        }

        $record = MedicalRecord::firstOrNew(['appointment_id' => $appointment->id]);
        if (! $record->exists || ! $this->isFinalized($record)) {
            return redirect()
                ->route('attendance.record.edit', $appointment)
                ->with('status', 'Atendimento ja esta aberto.');
        }

        $record->status_atendimento = 'em_andamento';
        $record->data_finalizacao = null;
        $record->save();

        return redirect()
            ->route('attendance.record.edit', $appointment)
            ->with('status', 'Atendimento reaberto.');
    }

    private function validateRecord(Request $request): array
    {
        return $request->validate([
            'tipo_atendimento' => ['required', 'string', 'in:consulta,retorno,urgencia,telemedicina'],
            'data_atendimento' => ['required', 'date'],
            'hora_inicio' => ['required', 'regex:/^\\d{2}:\\d{2}(:\\d{2})?$/'],
            'hora_fim' => ['nullable', 'regex:/^\\d{2}:\\d{2}(:\\d{2})?$/'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'status_atendimento' => ['required', 'string', 'in:em_andamento,finalizado,cancelado'],
            'queixa_principal' => ['nullable', 'string'],
            'historia_doenca_atual' => ['nullable', 'string'],
            'antecedentes_doencas_cronicas' => ['nullable', 'string'],
            'antecedentes_cirurgias' => ['nullable', 'string'],
            'antecedentes_alergias' => ['nullable', 'string'],
            'antecedentes_medicamentos' => ['nullable', 'string'],
            'peso_kg' => ['nullable', 'numeric', 'min:0'],
            'altura_cm' => ['nullable', 'numeric', 'min:0'],
            'pressao_arterial' => ['nullable', 'string', 'max:20'],
            'frequencia_cardiaca' => ['nullable', 'integer', 'min:0'],
            'frequencia_respiratoria' => ['nullable', 'integer', 'min:0'],
            'temperatura' => ['nullable', 'numeric', 'min:0'],
            'saturacao_o2' => ['nullable', 'integer', 'min:0', 'max:100'],
            'exame_fisico_geral' => ['nullable', 'string'],
            'exame_cardiovascular' => ['nullable', 'string'],
            'exame_respiratorio' => ['nullable', 'string'],
            'exame_abdome' => ['nullable', 'string'],
            'exame_neurologico' => ['nullable', 'string'],
            'exame_outros' => ['nullable', 'string'],
            'cid_principal' => ['nullable', 'string', 'max:20'],
            'cid_secundario' => ['nullable', 'string'],
            'descricao_diagnostico' => ['nullable', 'string'],
            'plano_terapeutico' => ['nullable', 'string'],
            'conduta_medica' => ['nullable', 'string'],
            'prescricao' => ['nullable', 'string'],
            'orientacoes_paciente' => ['nullable', 'string'],
            'solicita_exames_laboratoriais' => ['nullable'],
            'solicita_exames_imagem' => ['nullable'],
            'solicita_encaminhamento' => ['nullable'],
            'solicita_atestado' => ['nullable'],
            'solicita_receita' => ['nullable'],
            'documentos_gerados' => ['nullable', 'string'],
            'documentos_gerados_detalhes' => ['nullable', 'string'],
            'attachments_documents.*' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'attachments_exams.*' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'attachments_prescriptions.*' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'attachments_reports.*' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'attachments_certificates.*' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'medico_crm' => ['nullable', 'string', 'max:30'],
            'uf_crm' => ['nullable', 'string', 'max:5'],
            'assinatura_digital' => ['nullable', 'string', 'max:255'],
            'termo_lgpd_aceito' => ['nullable', 'boolean', 'required_if:status_atendimento,finalizado', 'accepted_if:status_atendimento,finalizado'],
        ]);
    }

    private function validateDocumentOnly(Request $request): array
    {
        return $request->validate([
            'documentos_gerados' => ['nullable', 'string'],
            'documentos_gerados_detalhes' => ['nullable', 'string'],
            'documentos_gerados_detalhes_touched' => ['nullable'],
            'solicita_exames_laboratoriais' => ['nullable'],
            'solicita_exames_imagem' => ['nullable'],
            'solicita_encaminhamento' => ['nullable'],
            'solicita_atestado' => ['nullable'],
            'solicita_receita' => ['nullable'],
        ]);
    }

    private function storeDocumentOnly(Request $request, MedicalRecord $record, Appointment $appointment): void
    {
        $data = $this->validateDocumentOnly($request);
        $rawDocs = $data['documentos_gerados'] ?? null;
        $rawDetails = $data['documentos_gerados_detalhes'] ?? null;
        $selectedDocs = $this->parseJsonList($rawDocs);
        $detailsMap = $this->parseJsonMap($rawDetails);

        if ($request->boolean('documentos_gerados_detalhes_touched')) {
            if ($rawDetails) {
                $decodedDetails = json_decode($rawDetails, true);
                if (is_array($decodedDetails)) {
                    $detailsMap = $decodedDetails;
                }
            }
            foreach ($selectedDocs as $label) {
                if (! array_key_exists($label, $detailsMap)) {
                    $detailsMap[$label] = '';
                }
            }
        }

        $record->fill([
            'documentos_gerados' => $this->encodeJsonOrNull($selectedDocs),
            'documentos_gerados_detalhes' => $this->encodeJsonOrNull($detailsMap),
            'solicita_exames_laboratoriais' => $request->boolean('solicita_exames_laboratoriais'),
            'solicita_exames_imagem' => $request->boolean('solicita_exames_imagem'),
            'solicita_encaminhamento' => $request->boolean('solicita_encaminhamento'),
            'solicita_atestado' => $request->boolean('solicita_atestado'),
            'solicita_receita' => $request->boolean('solicita_receita'),
            'professional_id' => $record->professional_id ?? $appointment->professional_id,
            'patient_id' => $record->patient_id ?? $appointment->patient_id,
            'unit_id' => $record->unit_id ?? $appointment->unit_id,
            'created_by' => $record->created_by ?? $request->user()?->id,
        ]);

        $record->save();
    }

    private function calculateImc($peso, $alturaCm): ?float
    {
        if (! $peso || ! $alturaCm) {
            return null;
        }
        $alturaM = (float) $alturaCm / 100;
        if ($alturaM <= 0) {
            return null;
        }
        return round(((float) $peso) / ($alturaM * $alturaM), 2);
    }

    private function parseCidList(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        $items = preg_split('/[,\n]+/', $raw);
        $items = array_map('trim', $items);
        $items = array_filter($items, fn ($item) => $item !== '');

        return array_values($items);
    }

    private function parseJsonList(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', $decoded), fn ($item) => $item !== ''));
    }

    private function parseJsonMap(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $clean = [];
        foreach ($decoded as $key => $value) {
            $label = trim((string) $key);
            if ($label === '') {
                continue;
            }
            $clean[$label] = is_string($value) ? $value : (string) $value;
        }

        return $clean;
    }

    private function normalizeTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/^\\d{2}:\\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^\\d{2}:\\d{2}:\\d{2}$/', $value)) {
            return substr($value, 0, 5);
        }

        return $value;
    }

    private function encodeJsonOrNull($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return json_encode($value);
    }

    private function encodeArrayValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = json_encode($value);
            }
        }

        return $data;
    }

    private function debugAttendancePayload(array $data): void
    {
        $types = [];
        $arrays = [];
        foreach ($data as $key => $value) {
            $types[$key] = is_array($value) ? 'array' : gettype($value);
            if (is_array($value)) {
                $arrays[$key] = $value;
            }
        }

        $payload = [
            'time' => now()->toDateTimeString(),
            'types' => $types,
            'arrays' => $arrays,
        ];

        @file_put_contents(storage_path('logs/attendance_debug.log'), json_encode($payload).PHP_EOL, FILE_APPEND);
    }

    private function storeAttachments(Request $request, MedicalRecord $record, Appointment $appointment): array
    {
        $map = [
            'attachments_documents' => 'anexos_documentos',
            'attachments_exams' => 'anexos_exames',
            'attachments_prescriptions' => 'anexos_receitas',
            'attachments_reports' => 'anexos_laudos',
            'attachments_certificates' => 'anexos_atestados',
        ];

        $updates = [];
        foreach ($map as $input => $column) {
            if (! $request->hasFile($input)) {
                continue;
            }
            $files = $request->file($input, []);
            $paths = $record->{$column} ?? [];
            foreach ($files as $file) {
                if (! $file) {
                    continue;
                }
                $paths[] = $file->store("attendance/{$appointment->id}/{$input}", 'public');
            }
            $updates[$column] = array_values(array_unique($paths));
        }

        return $updates;
    }

    private function fillFromPatient(MedicalRecord $record, ?Patient $patient): void
    {
        if (! $patient) {
            return;
        }

        $record->fill([
            'antecedentes_doencas_cronicas' => $patient->chronic_conditions,
            'antecedentes_cirurgias' => $patient->previous_surgeries,
            'antecedentes_alergias' => $patient->allergies,
            'antecedentes_medicamentos' => $patient->current_medications,
            'peso_kg' => $patient->weight_kg,
            'altura_cm' => $patient->height_cm,
            'pressao_arterial' => $patient->blood_pressure,
            'frequencia_cardiaca' => $patient->heart_rate,
            'frequencia_respiratoria' => $patient->respiratory_rate,
            'temperatura' => $patient->temperature,
        ]);
    }

    private function isFinalized(MedicalRecord $record): bool
    {
        return $record->status_atendimento === 'finalizado' || $record->data_finalizacao !== null;
    }
}
