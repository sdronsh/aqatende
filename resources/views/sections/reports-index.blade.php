<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Relatorios</h2>
            <p class="text-sm text-gray-600">Escolha o tipo de relatorio para definir filtros.</p>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
            <h3 class="text-sm font-semibold text-gray-800">Operacional</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'agenda') }}">Agenda</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'atendimentos') }}">Atendimentos realizados</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'cancelamentos') }}">Cancelamentos</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'faltas') }}">Faltas (No show)</a>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
            <h3 class="text-sm font-semibold text-gray-800">Financeiro</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'receita') }}">Receita</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'contas_receber') }}">Contas a receber</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'contas_pagar') }}">Contas a pagar</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'fluxo_caixa') }}">Fluxo de caixa</a>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
            <h3 class="text-sm font-semibold text-gray-800">Gestao</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'receita_profissional') }}">Receita por profissional</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'receita_servico') }}">Receita por servico</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'pacientes_novos') }}">Clientes novos</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'ocupacao_agenda') }}">Ocupacao da agenda</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'atendimentos_profissional') }}">Atendimentos por profissional</a>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
            <h3 class="text-sm font-semibold text-gray-800">Clientes</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'pacientes_lista') }}">Lista de clientes</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'pacientes_frequentes') }}">Clientes frequentes</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'pacientes_sem_retorno') }}">Clientes sem retorno</a>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm md:p-6">
            <h3 class="text-sm font-semibold text-gray-800">Estrategico</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'taxa_cancelamento') }}">Taxa de cancelamento</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'ticket_medio') }}">Ticket medio</a>
                <a class="rounded-lg border border-gray-200 p-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:border-brand-400 hover:text-brand-600" href="{{ route('finance.reports.show', 'tempo_medio') }}">Tempo medio de consulta</a>
            </div>
        </div>
    </div>
</x-app-layout>
