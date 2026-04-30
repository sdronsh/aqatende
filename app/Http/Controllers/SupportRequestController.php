<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SupportRequestController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'message.required' => 'Informe as informacoes para o suporte.',
            'message.max' => 'As informacoes para o suporte devem ter no maximo 5000 caracteres.',
        ]);

        $user = $request->user();
        $companyId = session('active_company_id');
        $company = $companyId ? Company::find($companyId) : null;

        try {
            Mail::raw(
                implode(PHP_EOL, [
                    'Solicitacao de suporte AQAtende',
                    '',
                    'Usuario: '.$user->name,
                    'E-mail: '.$user->email,
                    'Empresa: '.($company?->name ?? 'Sem empresa ativa'),
                    'URL: '.url()->previous(),
                    'Data/hora: '.now()->format('d/m/Y H:i:s'),
                    '',
                    'Mensagem:',
                    $data['message'],
                ]),
                function ($message) use ($user): void {
                    $message
                        ->to('suporte@aqatende.com.br')
                        ->replyTo($user->email, $user->name)
                        ->subject('Solicitacao de suporte AQAtende');
                }
            );
        } catch (Throwable $exception) {
            Log::error('Falha ao enviar solicitacao de suporte.', [
                'user_id' => $user->id,
                'company_id' => $companyId,
                'exception' => $exception,
            ]);

            return back()->with('error', 'Nao foi possivel enviar a duvida/problema ao suporte.');
        }

        return back()->with('status', 'support-request-sent');
    }
}
