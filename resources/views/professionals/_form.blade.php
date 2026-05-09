@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
@endphp

<div class="mb-4 border-b border-gray-200">
    <div class="flex flex-wrap gap-2 text-sm">
        <button type="button" class="rounded-t-lg border border-gray-200 bg-white px-4 py-2 font-medium text-gray-700" data-tab-button="dados">Dados</button>
        <button type="button" class="rounded-t-lg border border-gray-200 bg-white px-4 py-2 font-medium text-gray-700" data-tab-button="horarios">Horarios</button>
    </div>
</div>

<div data-tab-panel="dados">
    <div class="grid gap-4 md:grid-cols-12">
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="user_id">Usuario de acesso</label>
        @php $userId = old('user_id', $professional->user_id ?? null); @endphp
        <select class="{{ $input }}" id="user_id" name="user_id">
            <option value="">Selecione</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) $userId === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('user_id')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="display_name">Nome exibido</label>
        <input class="{{ $input }}" id="display_name" name="display_name" value="{{ old('display_name', $professional->display_name ?? '') }}" />
        <x-input-error class="mt-1" :messages="$errors->get('display_name')" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="phone">Telefone</label>
        <input class="{{ $input }}" id="phone" name="phone" value="{{ old('phone', $professional->phone ?? '') }}" />
        <x-input-error class="mt-1" :messages="$errors->get('phone')" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="email">E-mail</label>
        <input class="{{ $input }}" id="email" name="email" type="email" value="{{ old('email', $professional->email ?? '') }}" />
        <x-input-error class="mt-1" :messages="$errors->get('email')" />
    </div>
    <div class="md:col-span-4">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="salary_type">Tipo de remuneração</label>
        @php $salaryType = old('salary_type', $professional->salary_type ?? 'commission'); @endphp
        <select class="{{ $input }}" id="salary_type" name="salary_type">
            <option value="commission" @selected($salaryType === 'commission')>Comissão</option>
            <option value="fixed" @selected($salaryType === 'fixed')>Fixo</option>
            <option value="fixed_plus_commission" @selected($salaryType === 'fixed_plus_commission')>Fixo + comissão</option>
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('salary_type')" />
    </div>
    <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="fixed_salary">Salário fixo</label>
        <input class="{{ $input }}" id="fixed_salary" name="fixed_salary" value="{{ old('fixed_salary', isset($professional) ? number_format(($professional->fixed_salary_cents ?? 0) / 100, 2, ',', '.') : '') }}" />
        <x-input-error class="mt-1" :messages="$errors->get('fixed_salary')" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="commission_type">Tipo de comissão</label>
        @php $commissionType = old('commission_type', $professional->commission_type ?? 'percentage'); @endphp
        <select class="{{ $input }}" id="commission_type" name="commission_type">
            <option value="">Sem padrão</option>
            <option value="percentage" @selected($commissionType === 'percentage')>Percentual</option>
            <option value="fixed_value" @selected($commissionType === 'fixed_value')>Valor fixo</option>
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('commission_type')" />
    </div>
    <div class="md:col-span-3">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="commission_value">Comissão padrão</label>
        <input class="{{ $input }}" id="commission_value" name="commission_value" value="{{ old('commission_value', $professional->commission_value ?? '') }}" />
        <x-input-error class="mt-1" :messages="$errors->get('commission_value')" />
    </div>
    <div class="md:col-span-12">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="bio">Bio</label>
        <textarea class="{{ $input }}" id="bio" name="bio" rows="3">{{ old('bio', $professional->bio ?? '') }}</textarea>
        <x-input-error class="mt-1" :messages="$errors->get('bio')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-2 block text-sm font-medium text-gray-700">Categorias de atuação</label>
        @php $selectedSpecialties = old('specialties', $professional->specialties?->pluck('id')->all() ?? []); @endphp
        <div class="grid gap-2 rounded-lg border border-gray-200 bg-white p-3 sm:grid-cols-2">
            @foreach ($specialties as $specialty)
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="specialties[]" value="{{ $specialty->id }}" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked(in_array($specialty->id, $selectedSpecialties, true)) />
                    {{ $specialty->name }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="md:col-span-6">
        <label class="mb-2 block text-sm font-medium text-gray-700">Serviços atendidos</label>
        @php $selectedServices = old('services', $professional->services?->pluck('id')->all() ?? []); @endphp
        <div class="grid gap-2 rounded-lg border border-gray-200 bg-white p-3 sm:grid-cols-2">
            @foreach ($services as $service)
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="services[]" value="{{ $service->id }}" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked(in_array($service->id, $selectedServices, true)) />
                    {{ $service->name }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="md:col-span-6">
        <label class="mb-2 block text-sm font-medium text-gray-700">Unidades</label>
        @php $selectedUnits = old('units', $professional->units?->pluck('id')->all() ?? []); @endphp
        <div class="grid gap-2 rounded-lg border border-gray-200 bg-white p-3 sm:grid-cols-2">
            @foreach ($units as $unit)
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="units[]" value="{{ $unit->id }}" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked(in_array($unit->id, $selectedUnits, true)) />
                    {{ $unit->name }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="md:col-span-4">
        @php $active = old('active', $professional->active ?? true); @endphp
        <label class="inline-flex items-center gap-2 text-sm text-gray-600 mt-6">
            <input type="checkbox" name="active" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($active) />
            Profissional ativo
        </label>
    </div>
    </div>
</div>

<div data-tab-panel="horarios" class="hidden">
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <div class="mb-3 text-sm font-semibold text-gray-700">Agenda semanal</div>
        @php
            $weekdays = [
                1 => 'Segunda',
                2 => 'Terca',
                3 => 'Quarta',
                4 => 'Quinta',
                5 => 'Sexta',
                6 => 'Sabado',
                7 => 'Domingo',
            ];
        @endphp
        <div class="space-y-3">
            @foreach ($weekdays as $weekday => $label)
                @php
                    $oldSchedule = old('schedules.'.$weekday);
                    $savedSchedule = $schedulesByWeekday[$weekday] ?? [];
                    $isActive = $oldSchedule['is_active'] ?? ($savedSchedule['is_active'] ?? false);
                    $unitId = $oldSchedule['unit_id'] ?? ($savedSchedule['unit_id'] ?? ($units->count() === 1 ? $units->first()->id : null));
                    $slot1 = $savedSchedule['slot1'] ?? null;
                    $slot2 = $savedSchedule['slot2'] ?? null;
                    $startTime1 = $oldSchedule['slot1']['start_time'] ?? ($slot1->start_time ?? '');
                    $endTime1 = $oldSchedule['slot1']['end_time'] ?? ($slot1->end_time ?? '');
                    $startTime2 = $oldSchedule['slot2']['start_time'] ?? ($slot2->start_time ?? '');
                    $endTime2 = $oldSchedule['slot2']['end_time'] ?? ($slot2->end_time ?? '');
                @endphp
                <div class="flex flex-nowrap items-center gap-3 overflow-x-auto rounded-lg border border-gray-200 p-3">
                    <div class="w-32 flex-none">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="schedules[{{ $weekday }}][is_active]" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($isActive) />
                            {{ $label }}
                        </label>
                    </div>
                    <div class="w-72 flex-none">
                        <select class="{{ $input }}" name="schedules[{{ $weekday }}][unit_id]">
                            <option value="">Todas as unidades</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) $unitId === (string) $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-1" :messages="$errors->get('schedules.'.$weekday.'.unit_id')" />
                        <div class="mt-1 text-xs text-gray-400">Se vazio, aplica para todas as unidades selecionadas.</div>
                    </div>
                    <div class="w-24 flex-none">
                        <input class="{{ $input }}" type="time" name="schedules[{{ $weekday }}][slot1][start_time]" value="{{ $startTime1 }}" placeholder="Inicio 1" />
                        <x-input-error class="mt-1" :messages="$errors->get('schedules.'.$weekday.'.slot1.start_time')" />
                    </div>
                    <div class="w-24 flex-none">
                        <input class="{{ $input }}" type="time" name="schedules[{{ $weekday }}][slot1][end_time]" value="{{ $endTime1 }}" placeholder="Fim 1" />
                        <x-input-error class="mt-1" :messages="$errors->get('schedules.'.$weekday.'.slot1.end_time')" />
                    </div>
                    <div class="w-24 flex-none">
                        <input class="{{ $input }}" type="time" name="schedules[{{ $weekday }}][slot2][start_time]" value="{{ $startTime2 }}" placeholder="Inicio 2" />
                        <x-input-error class="mt-1" :messages="$errors->get('schedules.'.$weekday.'.slot2.start_time')" />
                    </div>
                    <div class="w-24 flex-none">
                        <input class="{{ $input }}" type="time" name="schedules[{{ $weekday }}][slot2][end_time]" value="{{ $endTime2 }}" placeholder="Fim 2" />
                        <x-input-error class="mt-1" :messages="$errors->get('schedules.'.$weekday.'.slot2.end_time')" />
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const buttons = document.querySelectorAll('[data-tab-button]');
        const panels = document.querySelectorAll('[data-tab-panel]');
        const setActive = (name) => {
            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.tabPanel !== name);
            });
            buttons.forEach((button) => {
                const isActive = button.dataset.tabButton === name;
                button.classList.toggle('bg-brand-50', isActive);
                button.classList.toggle('text-brand-600', isActive);
                button.classList.toggle('border-brand-200', isActive);
                button.classList.toggle('text-gray-700', !isActive);
            });
        };
        if (buttons.length) {
            setActive('dados');
            buttons.forEach((button) => {
                button.addEventListener('click', () => setActive(button.dataset.tabButton));
            });
        }
    });
</script>
