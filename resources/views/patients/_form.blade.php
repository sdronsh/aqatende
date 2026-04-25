@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $select = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $textarea = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
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
            <input class="{{ $input }}" type="date" id="birthdate" name="birthdate" value="{{ old('birthdate', optional($patient->birthdate ?? null)->format('Y-m-d')) }}" />
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
