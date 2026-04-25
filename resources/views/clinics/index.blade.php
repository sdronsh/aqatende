<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase text-gray-400">Cadastro</div>
                <h2 class="text-lg font-semibold text-gray-800">Clinicas</h2>
            </div>
            @if (($canCreateClinic ?? true))
                <a class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" href="{{ route('clinics.create') }}">+ Nova</a>
            @endif
        </div>
    </x-slot>
    <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
            <div class="text-sm font-medium text-gray-700">Lista de Clinicas</div>
            <form method="GET" action="{{ route('clinics.index') }}" class="flex w-full max-w-3xl flex-wrap items-center gap-2">
                <input class="w-full flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="search" value="{{ request('search') }}" placeholder="Buscar por nome" />
                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" name="per_page">
                    @php $selectedPerPage = $perPage ?? request('per_page', '10'); @endphp
                    <option value="10" @selected($selectedPerPage === '10')>10</option>
                    <option value="20" @selected($selectedPerPage === '20')>20</option>
                    <option value="50" @selected($selectedPerPage === '50')>50</option>
                    <option value="all" @selected($selectedPerPage === 'all')>Todos</option>
                </select>
                <button class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" type="submit">Buscar</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                        <th class="border border-gray-200 px-4 py-3">Nome</th>
                        <th class="border border-gray-200 px-4 py-3">E-mail</th>
                        <th class="border border-gray-200 px-4 py-3">Telefone</th>
                        <th class="border border-gray-200 px-4 py-3">Termo</th>
                        <th class="border border-gray-200 px-4 py-3 text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clinics as $clinic)
                        <tr class="odd:bg-gray-50">
                            <td class="border border-gray-200 px-4 py-3 font-medium text-gray-800">{{ $clinic->name }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $clinic->email }}</td>
                            <td class="border border-gray-200 px-4 py-3 text-gray-600">{{ $clinic->phone }}</td>
                            <td class="border border-gray-200 px-4 py-3">
                                @php
                                    $currentVersion = $termsVersion ?? null;
                                    $acceptedVersion = $clinic->terms_version;
                                    $acceptedAt = $clinic->terms_accepted_at;
                                    $isAcceptedCurrent = $acceptedAt && $acceptedVersion && $currentVersion && $acceptedVersion === $currentVersion;
                                @endphp
                                @if ($isAcceptedCurrent)
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                        Aceito v{{ $acceptedVersion }}
                                    </span>
                                    <div class="text-[11px] text-gray-400 mt-1">{{ optional($acceptedAt)->format('d/m/Y H:i') }}</div>
                                @elseif ($acceptedAt && $acceptedVersion && $currentVersion && $acceptedVersion !== $currentVersion)
                                    <span class="inline-flex items-center rounded-full border border-warning-200 bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-800">
                                        Atualizar v{{ $currentVersion }}
                                    </span>
                                    <div class="text-[11px] text-gray-400 mt-1">Aceito v{{ $acceptedVersion }}</div>
                                @else
                                    <span class="inline-flex items-center rounded-full border border-error-200 bg-error-50 px-2 py-0.5 text-xs font-medium text-error-700">
                                        Pendente
                                    </span>
                                @endif
                            </td>
                            <td class="border border-gray-200 px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a class="rounded-lg border border-brand-500 px-2 py-1 text-xs font-medium text-brand-500 hover:bg-brand-50" href="{{ route('clinics.edit', $clinic) }}">Editar</a>
                                    <form method="POST" action="{{ route('clinics.destroy', $clinic) }}" data-delete-clinic="{{ $clinic->name }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-error-500 px-2 py-1 text-xs font-medium text-error-500 hover:bg-error-50" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border border-gray-200 px-6 py-6 text-center text-gray-500">Nenhuma clinica cadastrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($clinics, 'links'))
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $clinics->links() }}
            </div>
        @endif
    </div>

    <dialog id="clinic-delete-modal" class="w-full max-w-lg rounded-xl border border-gray-200 p-0 shadow-theme-lg">
        <div class="flex flex-col gap-4 p-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Confirmar exclusao</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" data-close-clinic-delete>&times;</button>
            </div>
            <div class="space-y-2 text-sm text-gray-700">
                <div class="rounded-lg border border-warning-200 bg-warning-50 px-3 py-2 text-warning-800">
                    Atenção: essa ação remove todos os dados vinculados à clínica.
                </div>
                <div class="rounded-lg border border-error-200 bg-error-50 px-3 py-2 text-error-800">
                    Exclusão irreversível. Certifique-se de que é a clínica correta.
                </div>
                <p class="text-xs text-gray-500" id="clinic-delete-name"></p>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50" data-close-clinic-delete autofocus>Não</button>
                <button type="button" class="rounded-lg bg-error-500 px-4 py-2 text-sm font-semibold text-white shadow-theme-xs hover:bg-error-600" data-confirm-clinic-delete>Sim, excluir</button>
            </div>
        </div>
    </dialog>
</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('clinic-delete-modal');
        const nameEl = document.getElementById('clinic-delete-name');
        let activeForm = null;

        document.querySelectorAll('form[data-delete-clinic]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                activeForm = form;
                const clinicName = form.dataset.deleteClinic || '';
                if (nameEl) {
                    nameEl.textContent = clinicName ? `Clinica: ${clinicName}` : '';
                }
                modal?.showModal();
            });
        });

        modal?.querySelectorAll('[data-close-clinic-delete]').forEach((button) => {
            button.addEventListener('click', () => modal.close());
        });

        modal?.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.close();
            }
        });

        modal?.querySelector('[data-confirm-clinic-delete]')?.addEventListener('click', () => {
            if (activeForm) {
                activeForm.submit();
            }
        });
    });
</script>
