<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase text-gray-400">Agendamento</div>
                <h2 class="text-lg font-semibold text-gray-800">Bloqueios de agenda</h2>
            </div>
            <a class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" href="{{ route('agenda.index') }}">Ver agenda</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700">
            Revise os dados informados antes de continuar.
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,380px)_1fr]">
        <section class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-800">Novo bloqueio</h3>
                <p class="mt-1 text-sm text-gray-500">Bloqueie um dia inteiro ou uma faixa de horario para um profissional.</p>
            </div>
            <form method="POST" action="{{ route('schedule-blocks.store') }}" class="space-y-4 px-6 py-5">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="professional_id">Profissional</label>
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" id="professional_id" name="professional_id" required>
                        <option value="">Selecione</option>
                        @foreach ($professionals as $professional)
                            <option value="{{ $professional->id }}" @selected(old('professional_id') == $professional->id)>{{ $professional->display_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-1" :messages="$errors->get('professional_id')" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="date">Data</label>
                    <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="date" id="date" name="date" value="{{ old('date', now()->addDay()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
                    <x-input-error class="mt-1" :messages="$errors->get('date')" />
                </div>

                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500" type="checkbox" name="all_day" value="1" @checked(old('all_day', true)) data-all-day-toggle>
                    Dia inteiro
                </label>

                <div class="grid gap-3 sm:grid-cols-2" data-time-fields>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="start_time">Hora inicial</label>
                        <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="time" id="start_time" name="start_time" value="{{ old('start_time', '08:00') }}">
                        <x-input-error class="mt-1" :messages="$errors->get('start_time')" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="end_time">Hora final</label>
                        <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="time" id="end_time" name="end_time" value="{{ old('end_time', '18:00') }}">
                        <x-input-error class="mt-1" :messages="$errors->get('end_time')" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="reason">Motivo/observacao</label>
                    <input class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" id="reason" name="reason" value="{{ old('reason') }}" placeholder="Folga, ferias, reuniao externa...">
                    <x-input-error class="mt-1" :messages="$errors->get('reason')" />
                </div>

                <button class="w-full rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" type="submit">Criar bloqueio</button>
            </form>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <form method="GET" action="{{ route('schedule-blocks.index') }}" class="flex flex-wrap items-end gap-3">
                    <div class="min-w-64 flex-1">
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="filter_professional_id">Filtrar profissional</label>
                        <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" id="filter_professional_id" name="professional_id">
                            <option value="">Todos os profissionais</option>
                            @foreach ($professionals as $professional)
                                <option value="{{ $professional->id }}" @selected((string) ($filters['professional_id'] ?? '') === (string) $professional->id)>{{ $professional->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" type="submit">Filtrar</button>
                </form>
            </div>

            <div class="responsive-table-wrapper overflow-x-auto">
                <table class="responsive-table min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                            <th class="border border-gray-200 px-4 py-3">Profissional</th>
                            <th class="border border-gray-200 px-4 py-3">Inicio</th>
                            <th class="border border-gray-200 px-4 py-3">Fim</th>
                            <th class="border border-gray-200 px-4 py-3">Motivo</th>
                            <th class="border border-gray-200 px-4 py-3 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($blocks as $block)
                            <tr class="odd:bg-gray-50">
                                <td class="border border-gray-200 px-4 py-3 font-medium text-gray-800" data-label="Profissional">{{ $block->professional?->display_name }}</td>
                                <td class="border border-gray-200 px-4 py-3 text-gray-600" data-label="Inicio">{{ $block->starts_at?->format('d/m/Y H:i') }}</td>
                                <td class="border border-gray-200 px-4 py-3 text-gray-600" data-label="Fim">{{ $block->ends_at?->format('d/m/Y H:i') }}</td>
                                <td class="border border-gray-200 px-4 py-3 text-gray-600" data-label="Motivo">{{ $block->reason ?: 'Bloqueio de agenda' }}</td>
                                <td class="border border-gray-200 px-4 py-3" data-actions>
                                    <form method="POST" action="{{ route('schedule-blocks.destroy', $block) }}" class="flex justify-end" onsubmit="return confirm('Remover este bloqueio?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-error-500 px-2 py-1 text-xs font-medium text-error-600 hover:bg-error-50" type="submit">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="border border-gray-200 px-4 py-6 text-center text-gray-500">Nenhum bloqueio futuro encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4">
                {{ $blocks->links() }}
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const checkbox = document.querySelector('[data-all-day-toggle]');
            const fields = document.querySelector('[data-time-fields]');
            const update = () => fields?.classList.toggle('opacity-50', checkbox?.checked);
            checkbox?.addEventListener('change', update);
            update();
        });
    </script>
</x-app-layout>
