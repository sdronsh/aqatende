@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $select = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $textarea = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $birthdateValue = old('birthdate', optional($patient->birthdate ?? null)->format('Y-m-d'));
    $birthdateDisplay = $birthdateValue ? \Illuminate\Support\Carbon::parse($birthdateValue)->format('d/m/Y') : '';
@endphp

<div class="flex flex-wrap gap-2 border-b border-gray-200">
    <button class="rounded-t-lg border border-b-0 border-gray-200 bg-white px-4 py-2 font-medium text-gray-700" type="button" data-tab="identificacao">Identificação</button>
    <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="documentos">Documentos</button>
    <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="endereco">Endereço</button>
    <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="contato">Contato</button>
    <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="familia">Familiares</button>
</div>

<div class="space-y-4 pt-4" data-tab-pane="identificacao">
    <div class="grid gap-4 md:grid-cols-12">
        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="patient_id">ID</label>
            <input class="{{ $input }}" id="patient_id" value="{{ $patient->id ?? '-' }}" disabled />
        </div>
        <div class="md:col-span-5">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="full_name">Nome completo</label>
            <input class="{{ $input }}" id="full_name" name="full_name" value="{{ old('full_name', $patient->full_name ?? '') }}" required />
            <x-input-error class="mt-1" :messages="$errors->get('full_name')" />
        </div>
        <div class="md:col-span-5">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="social_name">Apelido / nome social</label>
            <input class="{{ $input }}" id="social_name" name="social_name" value="{{ old('social_name', $patient->social_name ?? '') }}" />
            <x-input-error class="mt-1" :messages="$errors->get('social_name')" />
        </div>
        <div class="md:col-span-3">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="birthdate">Data nascimento</label>
            <div class="relative" data-date-picker data-initial-date="{{ $birthdateValue }}">
                <input type="hidden" id="birthdate" name="birthdate" value="{{ $birthdateValue }}" data-date-picker-value />
                <div class="flex">
                    <input class="{{ $input }} rounded-r-none" id="birthdate_display" value="{{ $birthdateDisplay }}" placeholder="dd/mm/aaaa" inputmode="numeric" autocomplete="off" data-date-picker-display />
                    <button type="button" class="inline-flex h-[38px] w-11 items-center justify-center rounded-r-lg border border-l-0 border-gray-200 bg-white text-brand-600 shadow-theme-xs hover:bg-brand-50" data-date-picker-toggle aria-label="Abrir calendario">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7 3v3M17 3v3M4 8h16M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zM8 12h3M13 12h3M8 16h3" />
                        </svg>
                    </button>
                </div>
                <div class="absolute left-0 top-full z-50 mt-2 hidden w-80 max-w-[calc(100vw-2rem)] rounded-xl border border-gray-200 bg-white p-3 shadow-theme-lg" data-date-picker-panel>
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" data-date-picker-prev aria-label="Mes anterior">&lsaquo;</button>
                        <div class="text-center">
                            <div class="text-sm font-semibold text-gray-800" data-date-picker-title></div>
                            <select class="mt-1 rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs text-gray-600" data-date-picker-year></select>
                        </div>
                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" data-date-picker-next aria-label="Proximo mes">&rsaquo;</button>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold uppercase text-gray-400">
                        <div>Dom</div>
                        <div>Seg</div>
                        <div>Ter</div>
                        <div>Qua</div>
                        <div>Qui</div>
                        <div>Sex</div>
                        <div>Sab</div>
                    </div>
                    <div class="mt-2 grid grid-cols-7 gap-1" data-date-picker-days></div>
                    <div class="mt-3 flex justify-between border-t border-gray-100 pt-3">
                        <button type="button" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50" data-date-picker-clear>Limpar</button>
                        <button type="button" class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-600" data-date-picker-today>Hoje</button>
                    </div>
                </div>
            </div>
            <x-input-error class="mt-1" :messages="$errors->get('birthdate')" />
        </div>
        <div class="md:col-span-3">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="gender">Sexo</label>
            @php $gender = old('gender', $patient->gender ?? ''); @endphp
            <select class="{{ $select }}" id="gender" name="gender">
                <option value="">Selecione</option>
                <option value="masculino" @selected($gender === 'masculino')>Masculino</option>
                <option value="feminino" @selected($gender === 'feminino')>Feminino</option>
                <option value="outro" @selected($gender === 'outro')>Outro</option>
            </select>
        </div>
        <div class="md:col-span-3">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="marital_status">Estado civil</label>
            <input class="{{ $input }}" id="marital_status" name="marital_status" value="{{ old('marital_status', $patient->marital_status ?? '') }}" />
        </div>
        <div class="md:col-span-3">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="status">Status</label>
            @php $status = old('status', $patient->status ?? 'ativo'); @endphp
            <select class="{{ $select }}" id="status" name="status">
                <option value="ativo" @selected($status === 'ativo')>Ativo</option>
                <option value="inativo" @selected($status === 'inativo')>Inativo</option>
            </select>
        </div>
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="photo">Foto</label>
            <input class="{{ $input }}" id="photo" name="photo" type="file" accept="image/*" />
            @if (! empty($patient->photo_path))
                <img class="mt-2 h-20 w-20 rounded-lg object-cover" src="{{ \Illuminate\Support\Facades\Storage::url($patient->photo_path) }}" alt="Foto do cliente" />
            @endif
            <x-input-error class="mt-1" :messages="$errors->get('photo')" />
        </div>
    </div>
</div>

<div class="hidden space-y-4 pt-4" data-tab-pane="documentos">
    <div class="grid gap-4 md:grid-cols-12">
        <div class="md:col-span-3">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="cpf">CPF</label>
            <input class="{{ $input }}" id="cpf" name="cpf" value="{{ old('cpf', $patient->cpf ?? '') }}" />
            <x-input-error class="mt-1" :messages="$errors->get('cpf')" />
        </div>
        <div class="md:col-span-3">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="rg">RG</label>
            <input class="{{ $input }}" id="rg" name="rg" value="{{ old('rg', $patient->rg ?? '') }}" />
        </div>
        <div class="md:col-span-3">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="rg_issuer">Órgão emissor</label>
            <input class="{{ $input }}" id="rg_issuer" name="rg_issuer" value="{{ old('rg_issuer', $patient->rg_issuer ?? '') }}" />
        </div>
        <div class="md:col-span-3">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="rg_state">UF</label>
            <input class="{{ $input }}" id="rg_state" name="rg_state" value="{{ old('rg_state', $patient->rg_state ?? '') }}" />
        </div>
    </div>
</div>

<div class="hidden space-y-4 pt-4" data-tab-pane="endereco">
    <div class="grid gap-4 md:grid-cols-12">
        <div class="md:col-span-3">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="address_zip">CEP</label>
            <input class="{{ $input }}" id="address_zip" name="address_zip" value="{{ old('address_zip', $patient->address_zip ?? '') }}" />
        </div>
        <div class="md:col-span-5">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="address_street">Logradouro</label>
            <input class="{{ $input }}" id="address_street" name="address_street" value="{{ old('address_street', $patient->address_street ?? '') }}" />
        </div>
        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="address_number">Número</label>
            <input class="{{ $input }}" id="address_number" name="address_number" value="{{ old('address_number', $patient->address_number ?? '') }}" />
        </div>
        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="address_complement">Complemento</label>
            <input class="{{ $input }}" id="address_complement" name="address_complement" value="{{ old('address_complement', $patient->address_complement ?? '') }}" />
        </div>
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="address_district">Bairro</label>
            <input class="{{ $input }}" id="address_district" name="address_district" value="{{ old('address_district', $patient->address_district ?? '') }}" />
        </div>
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="address_city">Cidade</label>
            <input class="{{ $input }}" id="address_city" name="address_city" value="{{ old('address_city', $patient->address_city ?? '') }}" />
        </div>
        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="address_state">Estado</label>
            <input class="{{ $input }}" id="address_state" name="address_state" value="{{ old('address_state', $patient->address_state ?? '') }}" />
        </div>
        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="address_country">País</label>
            <input class="{{ $input }}" id="address_country" name="address_country" value="{{ old('address_country', $patient->address_country ?? 'BR') }}" />
        </div>
    </div>
</div>

<div class="hidden space-y-4 pt-4" data-tab-pane="contato">
    <div class="grid gap-4 md:grid-cols-12">
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="phone">Telefone</label>
            <input class="{{ $input }}" id="phone" name="phone" value="{{ old('phone', $patient->phone ?? '') }}" />
        </div>
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="cellphone">Celular</label>
            <input class="{{ $input }}" id="cellphone" name="cellphone" value="{{ old('cellphone', $patient->cellphone ?? '') }}" />
        </div>
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="email">E-mail</label>
            <input class="{{ $input }}" id="email" name="email" value="{{ old('email', optional($patient->user)->email) }}" />
            <x-input-error class="mt-1" :messages="$errors->get('email')" />
        </div>
        <div class="md:col-span-4">
            @php $whatsapp = old('whatsapp', $patient->whatsapp ?? false); @endphp
            <label class="mt-6 inline-flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="whatsapp" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($whatsapp) />
                WhatsApp
            </label>
        </div>
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="emergency_contact_name">Contato alternativo</label>
            <input class="{{ $input }}" id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name', $patient->emergency_contact_name ?? '') }}" />
        </div>
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="emergency_contact_phone">Telefone alternativo</label>
            <input class="{{ $input }}" id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $patient->emergency_contact_phone ?? '') }}" />
        </div>
    </div>
</div>

<div class="hidden space-y-4 pt-4" data-tab-pane="familia">
    <div class="grid gap-4 md:grid-cols-12">
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="mother_name">Nome da mãe</label>
            <input class="{{ $input }}" id="mother_name" name="mother_name" value="{{ old('mother_name', $patient->mother_name ?? '') }}" />
        </div>
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="father_name">Nome do pai</label>
            <input class="{{ $input }}" id="father_name" name="father_name" value="{{ old('father_name', $patient->father_name ?? '') }}" />
        </div>
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="legal_guardian_name">Responsável</label>
            <input class="{{ $input }}" id="legal_guardian_name" name="legal_guardian_name" value="{{ old('legal_guardian_name', $patient->legal_guardian_name ?? '') }}" />
        </div>
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="legal_guardian_phone">Telefone responsável</label>
            <input class="{{ $input }}" id="legal_guardian_phone" name="legal_guardian_phone" value="{{ old('legal_guardian_phone', $patient->legal_guardian_phone ?? '') }}" />
        </div>
        <div class="md:col-span-4">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="guardian_relationship">Relação</label>
            <input class="{{ $input }}" id="guardian_relationship" name="guardian_relationship" value="{{ old('guardian_relationship', $patient->guardian_relationship ?? '') }}" />
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const monthNames = [
            'Janeiro',
            'Fevereiro',
            'Marco',
            'Abril',
            'Maio',
            'Junho',
            'Julho',
            'Agosto',
            'Setembro',
            'Outubro',
            'Novembro',
            'Dezembro',
        ];

        const pad = (value) => String(value).padStart(2, '0');
        const toIsoDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
        const toDisplayDate = (date) => `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()}`;

        const parseIsoDate = (value) => {
            const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
            if (! match) {
                return null;
            }

            const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
            return toIsoDate(date) === value ? date : null;
        };

        const parseDisplayDate = (value) => {
            const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value || '');
            if (! match) {
                return null;
            }

            const date = new Date(Number(match[3]), Number(match[2]) - 1, Number(match[1]));
            return toDisplayDate(date) === value ? date : null;
        };

        const maskDisplayDate = (value) => value
            .replace(/\D/g, '')
            .slice(0, 8)
            .replace(/^(\d{2})(\d)/, '$1/$2')
            .replace(/^(\d{2})\/(\d{2})(\d)/, '$1/$2/$3');

        document.querySelectorAll('[data-date-picker]').forEach((picker) => {
            const hiddenInput = picker.querySelector('[data-date-picker-value]');
            const displayInput = picker.querySelector('[data-date-picker-display]');
            const toggleButton = picker.querySelector('[data-date-picker-toggle]');
            const panel = picker.querySelector('[data-date-picker-panel]');
            const title = picker.querySelector('[data-date-picker-title]');
            const yearSelect = picker.querySelector('[data-date-picker-year]');
            const daysGrid = picker.querySelector('[data-date-picker-days]');
            const previousButton = picker.querySelector('[data-date-picker-prev]');
            const nextButton = picker.querySelector('[data-date-picker-next]');
            const clearButton = picker.querySelector('[data-date-picker-clear]');
            const todayButton = picker.querySelector('[data-date-picker-today]');
            const today = new Date();
            const initialDate = parseIsoDate(hiddenInput.value);
            let selectedDate = initialDate;
            let viewDate = initialDate || new Date(today.getFullYear() - 25, today.getMonth(), today.getDate());

            const firstYear = today.getFullYear() - 120;
            const lastYear = today.getFullYear() + 1;
            for (let year = lastYear; year >= firstYear; year -= 1) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearSelect.appendChild(option);
            }

            const closePanel = () => panel.classList.add('hidden');
            const openPanel = () => {
                panel.classList.remove('hidden');
                renderCalendar();
            };

            const setSelectedDate = (date) => {
                selectedDate = date;
                hiddenInput.value = date ? toIsoDate(date) : '';
                displayInput.value = date ? toDisplayDate(date) : '';
                displayInput.setCustomValidity('');
                if (date) {
                    viewDate = new Date(date.getFullYear(), date.getMonth(), 1);
                }
                renderCalendar();
            };

            const renderCalendar = () => {
                title.textContent = monthNames[viewDate.getMonth()];
                yearSelect.value = viewDate.getFullYear();
                daysGrid.innerHTML = '';

                const firstDay = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
                const lastDay = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 0);

                for (let index = 0; index < firstDay.getDay(); index += 1) {
                    daysGrid.appendChild(document.createElement('span'));
                }

                for (let day = 1; day <= lastDay.getDate(); day += 1) {
                    const date = new Date(viewDate.getFullYear(), viewDate.getMonth(), day);
                    const button = document.createElement('button');
                    const isSelected = selectedDate && toIsoDate(selectedDate) === toIsoDate(date);
                    const isToday = toIsoDate(today) === toIsoDate(date);

                    button.type = 'button';
                    button.textContent = day;
                    button.className = [
                        'inline-flex h-9 w-9 items-center justify-center rounded-lg text-sm transition',
                        isSelected ? 'bg-brand-500 font-semibold text-white hover:bg-brand-600' : 'text-gray-700 hover:bg-brand-50 hover:text-brand-700',
                        isToday && ! isSelected ? 'ring-1 ring-brand-200' : '',
                    ].join(' ');
                    button.addEventListener('click', () => {
                        setSelectedDate(date);
                        closePanel();
                    });
                    daysGrid.appendChild(button);
                }
            };

            toggleButton.addEventListener('click', () => {
                panel.classList.contains('hidden') ? openPanel() : closePanel();
            });

            displayInput.addEventListener('focus', openPanel);
            displayInput.addEventListener('input', () => {
                displayInput.value = maskDisplayDate(displayInput.value);
                const typedDate = parseDisplayDate(displayInput.value);
                if (typedDate) {
                    selectedDate = typedDate;
                    hiddenInput.value = toIsoDate(typedDate);
                    viewDate = new Date(typedDate.getFullYear(), typedDate.getMonth(), 1);
                    displayInput.setCustomValidity('');
                    renderCalendar();
                    return;
                }

                hiddenInput.value = '';
                displayInput.setCustomValidity(displayInput.value ? 'Informe uma data valida.' : '');
            });
            displayInput.addEventListener('blur', () => {
                if (! displayInput.value) {
                    setSelectedDate(null);
                    return;
                }

                const date = parseDisplayDate(displayInput.value);
                if (! date) {
                    hiddenInput.value = '';
                    displayInput.setCustomValidity('Informe uma data valida.');
                    return;
                }

                setSelectedDate(date);
            });

            previousButton.addEventListener('click', () => {
                viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1);
                renderCalendar();
            });

            nextButton.addEventListener('click', () => {
                viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1);
                renderCalendar();
            });

            yearSelect.addEventListener('change', () => {
                viewDate = new Date(Number(yearSelect.value), viewDate.getMonth(), 1);
                renderCalendar();
            });

            clearButton.addEventListener('click', () => {
                setSelectedDate(null);
                closePanel();
            });

            todayButton.addEventListener('click', () => {
                setSelectedDate(today);
                closePanel();
            });

            document.addEventListener('click', (event) => {
                if (! picker.contains(event.target)) {
                    closePanel();
                }
            });

            renderCalendar();
        });

        const tabButtons = document.querySelectorAll('[data-tab]');
        const tabPanes = document.querySelectorAll('[data-tab-pane]');

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.getAttribute('data-tab');
                tabButtons.forEach((btn) => {
                    btn.classList.remove('bg-white', 'text-gray-700', 'border-b-0');
                    btn.classList.add('bg-gray-50', 'text-gray-500');
                });
                button.classList.add('bg-white', 'text-gray-700', 'border-b-0');
                button.classList.remove('bg-gray-50', 'text-gray-500');

                tabPanes.forEach((pane) => {
                    pane.classList.toggle('hidden', pane.getAttribute('data-tab-pane') !== target);
                });
            });
        });
    });
</script>
