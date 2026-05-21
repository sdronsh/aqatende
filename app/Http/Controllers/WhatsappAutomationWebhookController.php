<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\CompanySetting;
use App\Models\Patient;
use App\Models\PatientBookingLink;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Unit;
use App\Services\Communication\CommunicationClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class WhatsappAutomationWebhookController extends Controller
{
    public function __invoke(Request $request, CommunicationClient $communication): JsonResponse
    {
        $token = (string) config('aqamed.communication.webhook_token', '');
        if ($token !== '' && $request->header('X-Webhook-Token') !== $token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $sessionUuid = $this->extractSessionUuid($payload);
        $phone = $this->extractPhone($payload);
        $text = $this->extractText($payload);

        if ($sessionUuid === '' || $phone === '' || $text === '') {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $company = $this->resolveCompanyBySessionUuid($sessionUuid);
        if (! $company) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $automation = $this->getAutomation((int) $company['company_id']);
        if (! (bool) data_get($automation, 'flow.bot_enabled', false)) {
            return response()->json(['ok' => true, 'ignored' => true, 'reason' => 'bot_disabled']);
        }

        $stateKey = "whatsapp_flow_state_{$phone}";
        $state = $this->loadState((int) $company['company_id'], $stateKey);
        $lower = mb_strtolower($text);
        $patient = $this->resolvePatientByPhone((int) $company['company_id'], $phone);

        if ($this->isCancelCommand($lower)) {
            return $this->cancelFlow($communication, (int) $company['company_id'], $stateKey, $sessionUuid, $phone);
        }

        if ($lower === 'menu' || $lower === 'reiniciar') {
            $state = ['step' => 'start'];
        }

        if ($this->isAvailabilityLinkCommand($lower)) {
            return $this->sendAvailabilityLinkFlow($communication, (int) $company['company_id'], $stateKey, $sessionUuid, $phone, $patient);
        }

        if (($state['step'] ?? null) === 'open_appointment_options') {
            $appointment = $this->findOpenAppointmentForPatient((int) $company['company_id'], $patient, (int) ($state['appointment_id'] ?? 0));
            if (! $appointment) {
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                $this->send($communication, $sessionUuid, $phone, 'Nao encontrei mais esse agendamento em aberto. Envie *agendar* para iniciar um novo atendimento.');
                return response()->json(['ok' => true]);
            }

            if (trim($lower) === '1') {
                $this->saveState((int) $company['company_id'], $stateKey, [
                    'step' => 'confirm_open_appointment_cancel',
                    'appointment_id' => $appointment->id,
                ]);
                $this->send($communication, $sessionUuid, $phone, $this->appointmentCancellationConfirmationMessage($appointment));
                return response()->json(['ok' => true]);
            }

            if (trim($lower) === '2' || $this->isStartCommand($lower) || str_contains($lower, 'novo')) {
                return $this->startBookingFlow($communication, (int) $company['company_id'], $stateKey, $sessionUuid, $phone);
            }

            if (trim($lower) === '3') {
                return $this->cancelFlow($communication, (int) $company['company_id'], $stateKey, $sessionUuid, $phone);
            }

            $this->send($communication, $sessionUuid, $phone, "Opcao invalida. Responda:\n1 - Cancelar este agendamento\n2 - Solicitar um novo agendamento\n3 - Encerrar contato via WhatsApp");
            return response()->json(['ok' => true]);
        }

        if (($state['step'] ?? null) === 'confirm_open_appointment_cancel') {
            $appointment = $this->findOpenAppointmentForPatient((int) $company['company_id'], $patient, (int) ($state['appointment_id'] ?? 0));
            if (! $appointment) {
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                $this->send($communication, $sessionUuid, $phone, 'Nao encontrei mais esse agendamento em aberto.');
                return response()->json(['ok' => true]);
            }

            if ($this->isAffirmative($lower)) {
                $this->cancelAppointmentFromWhatsapp($appointment);
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                $this->send($communication, $sessionUuid, $phone, "Agendamento cancelado com sucesso.\n\nSe quiser iniciar um novo agendamento, envie *agendar*.");
                return response()->json(['ok' => true, 'appointment_cancelled' => true, 'appointment_id' => $appointment->id]);
            }

            if ($lower === 'voltar' || $this->isNegative($lower)) {
                $this->saveState((int) $company['company_id'], $stateKey, [
                    'step' => 'open_appointment_options',
                    'appointment_id' => $appointment->id,
                ]);
                $this->send($communication, $sessionUuid, $phone, $this->openAppointmentMessage($appointment, $patient));
                return response()->json(['ok' => true]);
            }

            $this->send($communication, $sessionUuid, $phone, $this->appointmentCancellationConfirmationMessage($appointment));
            return response()->json(['ok' => true]);
        }

        if (($state['step'] ?? 'start') === 'start') {
            $openAppointment = $this->findOpenAppointmentForPatient((int) $company['company_id'], $patient);
            if ($openAppointment) {
                $this->send($communication, $sessionUuid, $phone, $this->openAppointmentMessage($openAppointment, $patient));
                $this->saveState((int) $company['company_id'], $stateKey, [
                    'step' => 'open_appointment_options',
                    'appointment_id' => $openAppointment->id,
                ]);
                return response()->json(['ok' => true]);
            }

            if ($this->isGreetingCommand($lower)) {
                $this->send($communication, $sessionUuid, $phone, $this->welcomeMessage($automation, $patient));
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                return response()->json(['ok' => true]);
            }

            if (! $this->isStartCommand($lower)) {
                $this->send($communication, $sessionUuid, $phone, $this->welcomeMessage($automation, $patient));
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                return response()->json(['ok' => true]);
            }

            return $this->startBookingFlow($communication, (int) $company['company_id'], $stateKey, $sessionUuid, $phone);
        }

        if (($state['step'] ?? null) === 'service') {
            $index = (int) $text;
            if ($index === count($state['services'] ?? []) + 1) {
                return $this->cancelFlow($communication, (int) $company['company_id'], $stateKey, $sessionUuid, $phone);
            }

            $serviceId = $state['services'][$index - 1] ?? null;
            $service = $serviceId ? $this->findWhatsappService((int) $company['company_id'], (int) $serviceId) : null;
            if (! $service) {
                $this->send($communication, $sessionUuid, $phone, 'Opcao invalida. Envie o numero do servico.');
                return response()->json(['ok' => true]);
            }

            $professionals = $this->professionalsForService((int) $company['company_id'], $service);
            if ($professionals->isEmpty()) {
                $this->send($communication, $sessionUuid, $phone, 'Nao ha profissionais disponiveis para este servico.');
                return response()->json(['ok' => true]);
            }

            $lines = ["Servico: *{$service->name}*.", 'Escolha o profissional:'];
            $lines[] = '0. Qualquer profissional';
            foreach ($professionals as $idx => $professional) {
                $lines[] = ($idx + 1).'. '.$professional->display_name;
            }
            $lines[] = ($professionals->count() + 1).'. Cancelar atendimento';
            $this->send($communication, $sessionUuid, $phone, implode("\n", $lines));
            $this->saveState((int) $company['company_id'], $stateKey, [
                'step' => 'professional',
                'service_id' => $service->id,
                'professionals' => $professionals->pluck('id')->all(),
            ]);
            return response()->json(['ok' => true]);
        }

        if (($state['step'] ?? null) === 'professional') {
            $service = $this->findWhatsappService((int) $company['company_id'], (int) ($state['service_id'] ?? 0));
            if (! $service) {
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                $this->send($communication, $sessionUuid, $phone, 'Vamos recomecar. Envie *agendar*.');
                return response()->json(['ok' => true]);
            }

            $professionals = $this->professionalsForService((int) $company['company_id'], $service);
            $option = (int) $text;
            if ($option === count($state['professionals'] ?? []) + 1) {
                return $this->cancelFlow($communication, (int) $company['company_id'], $stateKey, $sessionUuid, $phone);
            }

            $professional = $option === 0
                ? $professionals->first()
                : (($state['professionals'][$option - 1] ?? null) ? $professionals->firstWhere('id', $state['professionals'][$option - 1]) : null);

            if (! $professional) {
                $this->send($communication, $sessionUuid, $phone, 'Opcao invalida. Envie o numero do profissional.');
                return response()->json(['ok' => true]);
            }

            $this->saveState((int) $company['company_id'], $stateKey, [
                'step' => 'datetime',
                'service_id' => $service->id,
                'professional_id' => $professional->id,
            ]);
            $this->send($communication, $sessionUuid, $phone, "Envie a data e hora desejada no formato *DD/MM HH:MM* (ex: 15/05 14:30).\nDigite *cancelar* para encerrar.");
            return response()->json(['ok' => true]);
        }

        if (($state['step'] ?? null) === 'datetime') {
            $service = $this->findWhatsappService((int) $company['company_id'], (int) ($state['service_id'] ?? 0));
            $professional = Professional::find((int) ($state['professional_id'] ?? 0));
            if (! $service || ! $professional) {
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                $this->send($communication, $sessionUuid, $phone, 'Nao consegui continuar. Envie *agendar* para reiniciar.');
                return response()->json(['ok' => true]);
            }

            $bookingWindowMonths = $this->bookingWindowMonths($automation);
            $scheduledAt = $this->parseDateTime($text, $bookingWindowMonths);
            if (! $scheduledAt) {
                $this->send($communication, $sessionUuid, $phone, 'Data/hora invalida. Use *DD/MM HH:MM* (ex: 15/05 14:30).');
                return response()->json(['ok' => true]);
            }

            if ($scheduledAt->isPast()) {
                $this->send($communication, $sessionUuid, $phone, 'Essa data/hora ja passou. Envie uma nova data futura no formato *DD/MM HH:MM*.');
                return response()->json(['ok' => true]);
            }

            if ($this->isOutsideBookingWindow($scheduledAt, $bookingWindowMonths)) {
                $this->send($communication, $sessionUuid, $phone, "A agenda automatica permite horarios em ate {$bookingWindowMonths} mes(es) a partir de hoje. Envie outro horario no formato *DD/MM HH:MM*.");
                return response()->json(['ok' => true]);
            }

            $unit = $this->resolveUnitForServiceAndProfessional($service, $professional, (int) $company['company_id']);
            if (! $unit) {
                $this->send($communication, $sessionUuid, $phone, 'Nao encontrei unidade ativa para esse servico.');
                return response()->json(['ok' => true]);
            }

            if (! $this->isProfessionalAvailable($professional->id, $unit->id, $scheduledAt, (int) ($service->duration_minutes ?: 30))) {
                $this->send($communication, $sessionUuid, $phone, 'Esse horario ja esta ocupado ou indisponivel. Por favor, envie outro horario no formato *DD/MM HH:MM*.');
                return response()->json(['ok' => true]);
            }

            if (! $patient) {
                $this->saveState((int) $company['company_id'], $stateKey, [
                    'step' => 'confirm_guest_create',
                    'service_id' => $service->id,
                    'professional_id' => $professional->id,
                    'scheduled_at' => $scheduledAt->toIso8601String(),
                    'unit_id' => $unit->id,
                ]);
                $this->send(
                    $communication,
                    $sessionUuid,
                    $phone,
                    "Nao encontramos seu cadastro. Quer agendar assim mesmo? Responda *sim* ou *nao*.\nDigite *cancelar* para encerrar."
                );
                return response()->json(['ok' => true]);
            }

            $appointment = $this->createAppointmentForFlow($service, $professional, $unit, $patient, $scheduledAt);

            $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
            $this->send(
                $communication,
                $sessionUuid,
                $phone,
                'Agendamento confirmado: '.$scheduledAt->format('d/m/Y H:i').' com '.$professional->display_name.' para '.$service->name.'.'
            );

            return response()->json(['ok' => true, 'appointment_id' => $appointment->id]);
        }

        if (($state['step'] ?? null) === 'collect_booking_link_guest_name') {
            $name = trim($text);
            if (mb_strlen($name) < 3) {
                $this->send($communication, $sessionUuid, $phone, "Nome muito curto. Informe seu nome completo.\nDigite *cancelar* para encerrar.");
                return response()->json(['ok' => true]);
            }

            $patient = $this->createPatientForFlow((int) $company['company_id'], $name, $phone);
            $link = $this->createBookingLinkForPatient((int) $company['company_id'], $patient);

            $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
            $this->send($communication, $sessionUuid, $phone, "Cadastro concluido. Acesse o link abaixo para escolher um horario disponivel:\n{$link}");

            return response()->json(['ok' => true, 'patient_id' => $patient->id]);
        }

        if (($state['step'] ?? null) === 'confirm_guest_create') {
            if ($this->isAffirmative($lower)) {
                $this->saveState((int) $company['company_id'], $stateKey, array_merge($state, [
                    'step' => 'collect_guest_name',
                ]));
                $this->send($communication, $sessionUuid, $phone, 'Perfeito. Qual o seu nome completo?');
                return response()->json(['ok' => true]);
            }

            if ($this->isNegative($lower)) {
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                $this->send($communication, $sessionUuid, $phone, 'Sem problema. Quando quiser, envie uma nova mensagem para iniciar novamente.');
                return response()->json(['ok' => true]);
            }

            $this->send($communication, $sessionUuid, $phone, "Responda *sim* para continuar ou *nao* para cancelar.\nDigite *cancelar* para encerrar.");
            return response()->json(['ok' => true]);
        }

        if (($state['step'] ?? null) === 'collect_guest_name') {
            $name = trim($text);
            if (mb_strlen($name) < 3) {
                $this->send($communication, $sessionUuid, $phone, "Nome muito curto. Informe seu nome completo.\nDigite *cancelar* para encerrar.");
                return response()->json(['ok' => true]);
            }

            $service = $this->findWhatsappService((int) $company['company_id'], (int) ($state['service_id'] ?? 0));
            $professional = Professional::find((int) ($state['professional_id'] ?? 0));
            $unit = Unit::find((int) ($state['unit_id'] ?? 0));
            $scheduledAt = isset($state['scheduled_at']) ? Carbon::parse((string) $state['scheduled_at']) : null;

            if (! $service || ! $professional || ! $unit || ! $scheduledAt) {
                $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
                $this->send($communication, $sessionUuid, $phone, 'Nao consegui concluir. Envie *agendar* para recomecar.');
                return response()->json(['ok' => true]);
            }

            $bookingWindowMonths = $this->bookingWindowMonths($automation);
            if ($scheduledAt->isPast() || $this->isOutsideBookingWindow($scheduledAt, $bookingWindowMonths)) {
                $this->saveState((int) $company['company_id'], $stateKey, [
                    'step' => 'datetime',
                    'service_id' => $service->id,
                    'professional_id' => $professional->id,
                ]);
                $this->send($communication, $sessionUuid, $phone, "Esse horario nao esta mais dentro da janela de agendamento automatico. Envie outro horario em ate {$bookingWindowMonths} mes(es), no formato *DD/MM HH:MM*.");
                return response()->json(['ok' => true]);
            }

            if (! $this->isProfessionalAvailable($professional->id, $unit->id, $scheduledAt, (int) ($service->duration_minutes ?: 30))) {
                $this->saveState((int) $company['company_id'], $stateKey, [
                    'step' => 'datetime',
                    'service_id' => $service->id,
                    'professional_id' => $professional->id,
                ]);
                $this->send($communication, $sessionUuid, $phone, 'Esse horario ja esta ocupado ou indisponivel. Por favor, envie outro horario no formato *DD/MM HH:MM*.');
                return response()->json(['ok' => true]);
            }

            $patient = $this->createPatientForFlow((int) $company['company_id'], $name, $phone);
            $appointment = $this->createAppointmentForFlow($service, $professional, $unit, $patient, $scheduledAt);

            $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
            $this->send(
                $communication,
                $sessionUuid,
                $phone,
                'Cadastro concluido e agendamento confirmado: '.$scheduledAt->format('d/m/Y H:i').' com '.$professional->display_name.' para '.$service->name.'.'
            );

            return response()->json(['ok' => true, 'appointment_id' => $appointment->id, 'patient_id' => $patient->id]);
        }

        $this->saveState((int) $company['company_id'], $stateKey, ['step' => 'start']);
        return response()->json(['ok' => true]);
    }

    private function send(CommunicationClient $communication, string $sessionUuid, string $phone, string $text): void
    {
        $communication->sendWhatsappMessage($sessionUuid, $phone, $text);
    }

    private function cancelFlow(CommunicationClient $communication, int $companyId, string $stateKey, string $sessionUuid, string $phone): JsonResponse
    {
        $this->saveState($companyId, $stateKey, ['step' => 'start']);
        $this->send($communication, $sessionUuid, $phone, 'Atendimento encerrado. Quando quiser iniciar novamente, envie uma nova mensagem.');

        return response()->json(['ok' => true, 'cancelled' => true]);
    }

    private function startBookingFlow(CommunicationClient $communication, int $companyId, string $stateKey, string $sessionUuid, string $phone): JsonResponse
    {
        $services = $this->servicesForCompany($companyId);
        if ($services->isEmpty()) {
            $this->send($communication, $sessionUuid, $phone, 'No momento nao ha servicos habilitados para agendamento via WhatsApp.');
            return response()->json(['ok' => true]);
        }

        $lines = ["Perfeito. Escolha o servico (responda com o numero):"];
        foreach ($services as $idx => $service) {
            $lines[] = ($idx + 1).'. '.$service->name;
        }
        $lines[] = ($services->count() + 1).'. Cancelar atendimento';

        $this->send($communication, $sessionUuid, $phone, implode("\n", $lines));
        $this->saveState($companyId, $stateKey, [
            'step' => 'service',
            'services' => $services->pluck('id')->all(),
        ]);

        return response()->json(['ok' => true]);
    }

    private function sendAvailabilityLinkFlow(CommunicationClient $communication, int $companyId, string $stateKey, string $sessionUuid, string $phone, ?Patient $patient): JsonResponse
    {
        if (! $patient) {
            $this->saveState($companyId, $stateKey, ['step' => 'collect_booking_link_guest_name']);
            $this->send($communication, $sessionUuid, $phone, "Para enviar o link de horarios disponiveis, informe seu nome completo.\nDigite *cancelar* para encerrar.");

            return response()->json(['ok' => true]);
        }

        $link = $this->createBookingLinkForPatient($companyId, $patient);
        $this->saveState($companyId, $stateKey, ['step' => 'start']);
        $this->send($communication, $sessionUuid, $phone, "Acesse o link abaixo para escolher um horario disponivel:\n{$link}");

        return response()->json(['ok' => true, 'patient_id' => $patient->id]);
    }

    private function findOpenAppointmentForPatient(int $companyId, ?Patient $patient, ?int $appointmentId = null): ?Appointment
    {
        if (! $patient) {
            return null;
        }

        $clinicIds = Clinic::query()->where('company_id', $companyId)->pluck('id');

        return Appointment::query()
            ->with(['service', 'services', 'professional', 'unit'])
            ->whereIn('clinic_id', $clinicIds)
            ->where('patient_id', $patient->id)
            ->whereIn('status', ['agendado', 'scheduled'])
            ->where('scheduled_at', '>=', now())
            ->when($appointmentId, fn ($query) => $query->whereKey($appointmentId))
            ->orderBy('scheduled_at')
            ->first();
    }

    private function openAppointmentMessage(Appointment $appointment, ?Patient $patient): string
    {
        $scheduledAt = $appointment->scheduled_at;
        $clientName = $this->patientGreetingName($patient);
        $serviceName = $appointment->serviceNames();
        $professionalName = $appointment->professional?->display_name ?? 'Profissional a definir';
        $unitName = $appointment->unit?->name ?? 'Unidade a definir';
        $date = $scheduledAt?->format('d/m/Y') ?? '-';
        $time = $scheduledAt?->format('H:i') ?? '-';

        return "Olá {$clientName}. Encontrei um agendamento em aberto para você:\n\n"
            ."Serviço: {$serviceName}\n"
            ."Profissional: {$professionalName}\n"
            ."Unidade: {$unitName}\n"
            ."Data: {$date} às {$time}\n\n"
            ."Como deseja continuar?\n"
            ."1 - Cancelar este agendamento\n"
            ."2 - Solicitar um novo agendamento\n"
            ."3 - Encerrar contato via WhatsApp";
    }

    private function appointmentCancellationConfirmationMessage(Appointment $appointment): string
    {
        $scheduledAt = $appointment->scheduled_at;
        $date = $scheduledAt?->format('d/m/Y') ?? '-';
        $time = $scheduledAt?->format('H:i') ?? '-';

        return "Para confirmar o cancelamento do agendamento de {$date} às {$time}, responda *SIM*.\n\nSe quiser voltar, responda *VOLTAR*.";
    }

    private function cancelAppointmentFromWhatsapp(Appointment $appointment): void
    {
        $appointment->update([
            'status' => 'cancelado',
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelado pelo cliente via WhatsApp.',
        ]);

        foreach ($appointment->services()->pluck('services.id') as $serviceId) {
            $appointment->services()->updateExistingPivot($serviceId, ['status' => 'cancelado']);
        }
    }

    private function extractSessionUuid(array $payload): string
    {
        foreach ([
            'session.uuid',
            'session_uuid',
            'sessionUuid',
            'session.id',
            'instance.uuid',
            'instance.session_uuid',
            'instance.sessionUuid',
            'instanceUuid',
            'data.session.uuid',
            'data.session_uuid',
            'data.sessionUuid',
        ] as $key) {
            $value = trim((string) data_get($payload, $key, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function extractPhone(array $payload): string
    {
        foreach ([
            'message.phone_number',
            'phone_number',
            'message.from',
            'message.sender',
            'message.remoteJid',
            'message.remote_jid',
            'message.key.remoteJid',
            'key.remoteJid',
            'data.phone_number',
            'data.from',
            'data.sender',
            'data.remoteJid',
            'data.key.remoteJid',
            'from',
            'sender',
            'remoteJid',
            'remote_jid',
        ] as $key) {
            $value = trim((string) data_get($payload, $key, ''));
            $digits = preg_replace('/\D+/', '', $value) ?: '';
            if ($digits !== '') {
                return $digits;
            }
        }

        return $this->extractPhoneFromPayloadValues($payload);
    }

    private function extractPhoneFromPayloadValues(mixed $payload): string
    {
        if (is_array($payload)) {
            foreach ($payload as $value) {
                $phone = $this->extractPhoneFromPayloadValues($value);
                if ($phone !== '') {
                    return $phone;
                }
            }

            return '';
        }

        if (is_object($payload)) {
            return $this->extractPhoneFromPayloadValues((array) $payload);
        }

        $value = trim((string) $payload);
        if ($value === '') {
            return '';
        }

        if (preg_match('/(?:^|[^0-9])((?:55)?[1-9]{2}9?[0-9]{8})(?:@(?:s\.whatsapp\.net|c\.us)|[^0-9]|$)/', $value, $matches)) {
            return $matches[1];
        }

        $digits = preg_replace('/\D+/', '', $value) ?: '';
        if (strlen($digits) >= 10 && strlen($digits) <= 13) {
            return $digits;
        }

        return '';
    }

    private function extractText(array $payload): string
    {
        foreach ([
            'message.body',
            'message.text',
            'message.conversation',
            'message.extendedTextMessage.text',
            'message.message.conversation',
            'message.message.extendedTextMessage.text',
            'body',
            'text',
            'data.body',
            'data.text',
            'data.message.body',
            'data.message.text',
            'data.message.conversation',
            'data.message.extendedTextMessage.text',
            'data.message.message.conversation',
            'data.message.message.extendedTextMessage.text',
        ] as $key) {
            $value = trim((string) data_get($payload, $key, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function resolveCompanyBySessionUuid(string $uuid): ?array
    {
        $rows = CompanySetting::query()
            ->where('key', 'whatsapp_session')
            ->get(['company_id', 'value']);

        foreach ($rows as $row) {
            $decoded = json_decode((string) $row->value, true);
            if (is_array($decoded) && (string) ($decoded['uuid'] ?? '') === $uuid) {
                return ['company_id' => (int) $row->company_id];
            }
        }

        return null;
    }

    private function servicesForCompany(int $companyId)
    {
        $clinicIds = Clinic::query()->where('company_id', $companyId)->pluck('id');
        return Service::query()
            ->whereIn('clinic_id', $clinicIds)
            ->where('active', true)
            ->where('whatsapp_booking_enabled', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function findWhatsappService(int $companyId, int $serviceId): ?Service
    {
        if ($serviceId <= 0) {
            return null;
        }

        $clinicIds = Clinic::query()->where('company_id', $companyId)->pluck('id');

        return Service::query()
            ->whereIn('clinic_id', $clinicIds)
            ->where('active', true)
            ->where('whatsapp_booking_enabled', true)
            ->whereKey($serviceId)
            ->first();
    }

    private function professionalsForService(int $companyId, Service $service)
    {
        return Professional::query()
            ->where('active', true)
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereHas('user.companies', fn ($companyQuery) => $companyQuery->whereKey($companyId));
            })
            ->whereHas('services', function ($query) use ($service) {
                $query->where('services.id', $service->id)
                    ->where('professional_service.active', true);
            })
            ->orderBy('display_name')
            ->get(['id', 'display_name']);
    }

    private function resolveUnitForServiceAndProfessional(Service $service, Professional $professional, int $companyId): ?Unit
    {
        $clinicIds = Clinic::query()->where('company_id', $companyId)->pluck('id');

        return Unit::query()
            ->whereIn('clinic_id', $clinicIds)
            ->where('active', true)
            ->when($service->unit_id, fn ($query) => $query->whereKey($service->unit_id))
            ->whereHas('professionals', fn ($query) => $query->where('professionals.id', $professional->id))
            ->orderBy('name')
            ->first();
    }

    private function isProfessionalAvailable(int $professionalId, int $unitId, Carbon $start, int $duration): bool
    {
        $end = $start->copy()->addMinutes($duration);

        $hasConflict = Appointment::query()
            ->where('professional_id', $professionalId)
            ->where('unit_id', $unitId)
            ->whereNotIn('status', ['cancelado', 'cancelled'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('scheduled_at', [$start, $end->copy()->subSecond()])
                    ->orWhereBetween('ends_at', [$start->copy()->addSecond(), $end])
                    ->orWhere(function ($inner) use ($start, $end) {
                        $inner->where('scheduled_at', '<', $start)->where('ends_at', '>', $end);
                    });
            })
            ->exists();

        if ($hasConflict) {
            return false;
        }

        $schedules = Schedule::query()
            ->where('professional_id', $professionalId)
            ->where('unit_id', $unitId)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            return true;
        }

        $weekday = $start->dayOfWeekIso;
        $daySchedules = $schedules->where('weekday', $weekday);
        if ($daySchedules->isEmpty()) {
            return false;
        }

        $startMinutes = ((int) $start->format('H')) * 60 + ((int) $start->format('i'));
        $endMinutes = ((int) $end->format('H')) * 60 + ((int) $end->format('i'));

        foreach ($daySchedules as $schedule) {
            [$startHour, $startMin] = array_map('intval', explode(':', substr((string) $schedule->start_time, 0, 5)));
            [$endHour, $endMin] = array_map('intval', explode(':', substr((string) $schedule->end_time, 0, 5)));
            $slotStart = ($startHour * 60) + $startMin;
            $slotEnd = ($endHour * 60) + $endMin;
            if ($startMinutes >= $slotStart && $endMinutes <= $slotEnd) {
                return true;
            }
        }

        return false;
    }

    private function resolvePatientByPhone(int $companyId, string $phone): ?Patient
    {
        $incomingCandidates = $this->phoneCandidates($phone);

        $patients = Patient::query()
            ->select(['id', 'full_name', 'social_name', 'phone', 'cellphone'])
            ->whereHas('companies', fn ($query) => $query->where('companies.id', $companyId))
            ->orderBy('id')
            ->get();

        foreach ($patients as $patient) {
            $patientCandidates = array_merge(
                $this->phoneCandidates((string) ($patient->cellphone ?? '')),
                $this->phoneCandidates((string) ($patient->phone ?? '')),
            );
            if (! empty(array_intersect($incomingCandidates, $patientCandidates))) {
                return $patient;
            }
        }

        return null;
    }

    private function phoneCandidates(string $value): array
    {
        $digits = preg_replace('/\D+/', '', $value) ?: '';
        if ($digits === '') {
            return [];
        }

        $set = [];
        $set[$digits] = true;

        if (str_starts_with($digits, '55')) {
            if (strlen($digits) === 13 && ($digits[4] ?? '') === '9') {
                $set[substr($digits, 0, 4).substr($digits, 5)] = true;
            }

            if (strlen($digits) === 12) {
                $set[substr($digits, 0, 4).'9'.substr($digits, 4)] = true;
            }

            $local = substr($digits, 2);
            $set[$local] = true;
            if (strlen($local) === 11 && ($local[2] ?? '') === '9') {
                $set[substr($local, 0, 2).substr($local, 3)] = true;
            }
            if (strlen($local) === 10) {
                $set[substr($local, 0, 2).'9'.substr($local, 2)] = true;
            }
        } else {
            $set['55'.$digits] = true;
            if (strlen($digits) === 11 && ($digits[2] ?? '') === '9') {
                $set['55'.substr($digits, 0, 2).substr($digits, 3)] = true;
            }
            if (strlen($digits) === 10) {
                $set['55'.substr($digits, 0, 2).'9'.substr($digits, 2)] = true;
            }
        }

        return array_keys($set);
    }

    private function parseDateTime(string $text, int $bookingWindowMonths): ?Carbon
    {
        if (! preg_match('/^\s*(\d{2})\/(\d{2})\s+(\d{2}):(\d{2})\s*$/', $text, $matches)) {
            return null;
        }

        $timezone = (string) config('aqamed.whatsapp.timezone', 'America/Sao_Paulo');
        $now = now($timezone);
        $year = $now->year;
        $candidate = $this->createDateTimeFromParts($matches, $year, $timezone);
        if (! $candidate) {
            return null;
        }

        if ($candidate->isPast()) {
            $nextYearCandidate = $this->createDateTimeFromParts($matches, $year + 1, $timezone);
            if ($nextYearCandidate && ! $this->isOutsideBookingWindow($nextYearCandidate, $bookingWindowMonths)) {
                return $nextYearCandidate;
            }
        }

        return $candidate;
    }

    private function createDateTimeFromParts(array $matches, int $year, string $timezone): ?Carbon
    {
        $dateTime = "{$matches[1]}/{$matches[2]}/{$year} {$matches[3]}:{$matches[4]}";
        try {
            $candidate = Carbon::createFromFormat('d/m/Y H:i', $dateTime, $timezone);
        } catch (\Throwable) {
            return null;
        }

        if (! $candidate || $candidate->format('d/m/Y H:i') !== $dateTime) {
            return null;
        }

        return $candidate;
    }

    private function bookingWindowMonths(array $automation): int
    {
        return min(max((int) data_get($automation, 'flow.booking_window_months', 3), 1), 12);
    }

    private function isOutsideBookingWindow(Carbon $scheduledAt, int $bookingWindowMonths): bool
    {
        $timezone = (string) config('aqamed.whatsapp.timezone', 'America/Sao_Paulo');
        $limit = now($timezone)->addMonthsNoOverflow($bookingWindowMonths)->endOfDay();

        return $scheduledAt->greaterThan($limit);
    }

    private function loadState(int $companyId, string $key): array
    {
        $value = CompanySetting::query()->where('company_id', $companyId)->where('key', $key)->value('value');
        $decoded = is_string($value) ? json_decode($value, true) : null;
        return is_array($decoded) ? $decoded : ['step' => 'start'];
    }

    private function saveState(int $companyId, string $key, array $state): void
    {
        CompanySetting::updateOrCreate(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => json_encode($state)]
        );
    }

    private function getAutomation(int $companyId): array
    {
        $raw = CompanySetting::query()
            ->where('company_id', $companyId)
            ->where('key', 'whatsapp_automation')
            ->value('value');

        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }

    private function isAffirmative(string $value): bool
    {
        $value = trim(mb_strtolower($value));
        return in_array($value, ['sim', 's', 'ok', 'pode', 'yes', 'y'], true);
    }

    private function isNegative(string $value): bool
    {
        $value = trim(mb_strtolower($value));
        return in_array($value, ['nao', 'não', 'n', 'cancelar', 'no'], true);
    }

    private function isCancelCommand(string $value): bool
    {
        $value = trim(mb_strtolower($value));

        return in_array($value, ['cancelar', 'cancela', 'sair', 'encerrar', 'parar', 'fim'], true);
    }

    private function isStartCommand(string $value): bool
    {
        $value = trim(mb_strtolower($value));

        return str_contains($value, 'agendar')
            || str_contains($value, 'agendamento')
            || $value === 'agenda'
            || str_contains($value, 'horario')
            || str_contains($value, 'horário')
            || $value === '1';
    }

    private function isAvailabilityLinkCommand(string $value): bool
    {
        $value = trim(mb_strtolower($value));

        return in_array($value, ['disponivel', 'disponível', 'disponibilidade', 'horarios disponiveis', 'horários disponíveis'], true);
    }

    private function isGreetingCommand(string $value): bool
    {
        $value = trim(mb_strtolower($value));

        return in_array($value, ['oi', 'ola', 'olá', 'bom dia', 'boa tarde', 'boa noite'], true);
    }

    private function welcomeMessage(array $automation, ?Patient $patient = null): string
    {
        $message = trim((string) data_get($automation, 'templates.welcome', ''));
        $clientName = $this->patientGreetingName($patient);

        $message = $message !== ''
            ? str_replace(['{cliente}', '{nome}'], $clientName, $message)
            : "Oi! Responda *agendar* para iniciar seu agendamento pelo atendimento automatico.\nOu responda *disponivel* para receber um link com os horarios disponiveis.";

        return rtrim($message)."\n\nEnvie *agendar* para seguir pelo atendimento automatico ou *disponivel* para receber um link com os horarios disponiveis.";
    }

    private function patientGreetingName(?Patient $patient): string
    {
        if (! $patient) {
            return 'cliente';
        }

        $socialName = trim((string) ($patient->social_name ?? ''));
        if ($socialName !== '') {
            return $socialName;
        }

        $fullName = trim((string) ($patient->full_name ?? ''));

        return $fullName !== '' ? $fullName : 'cliente';
    }

    private function createPatientForFlow(int $companyId, string $name, string $phone): Patient
    {
        $digits = $this->normalizeBrazilLocalPhone($phone);
        $digits = $this->normalizeBrazilMobileDigits($digits);
        $formatted = $this->formatBrazilPhone($digits);

        $patient = Patient::create([
            'full_name' => $name,
            'social_name' => null,
            'status' => 'ativo',
            'cellphone' => $formatted,
            'phone' => $formatted,
            'whatsapp' => true,
            'admin_notes' => 'Cadastro criado automaticamente via WhatsApp.',
            'created_by_name' => 'WhatsApp Bot',
        ]);
        $patient->companies()->syncWithoutDetaching([$companyId]);

        return $patient;
    }

    private function normalizeBrazilLocalPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    private function normalizeBrazilMobileDigits(string $digits): string
    {
        if (strlen($digits) === 10) {
            return substr($digits, 0, 2).'9'.substr($digits, 2);
        }

        if (strlen($digits) > 11) {
            $digits = substr($digits, -11);
        }

        return $digits;
    }

    private function formatBrazilPhone(string $digits): string
    {
        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
        }

        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
        }

        return $digits;
    }

    private function createAppointmentForFlow(Service $service, Professional $professional, Unit $unit, Patient $patient, Carbon $scheduledAt): Appointment
    {
        $duration = (int) ($service->duration_minutes ?: 30);

        return Appointment::create([
            'clinic_id' => $unit->clinic_id,
            'unit_id' => $unit->id,
            'professional_id' => $professional->id,
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'status' => 'agendado',
            'channel' => 'whatsapp',
            'scheduled_at' => $scheduledAt,
            'ends_at' => $scheduledAt->copy()->addMinutes($duration),
            'duration_minutes' => $duration,
            'notes' => 'Agendado automaticamente via WhatsApp.',
            'price_cents' => (int) ($service->price_cents ?? 0),
            'payment_status' => 'pending',
        ]);
    }

    private function createBookingLinkForPatient(int $companyId, Patient $patient): string
    {
        $bookingLink = PatientBookingLink::create([
            'company_id' => $companyId,
            'patient_id' => $patient->id,
            'created_by' => null,
            'token' => Str::random(48),
            'expires_at' => now()->addDays(7),
        ]);

        return route('public.booking.show', $bookingLink->token);
    }
}
