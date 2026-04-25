@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $grouped = $permissions->groupBy(['module', 'resource']);
    $selected = old('permissions', $selected ?? []);
    $actionLabels = [
        'view' => 'Visualizar',
        'list' => 'Listar',
        'create' => 'Criar',
        'update' => 'Editar',
        'delete' => 'Excluir',
        'manage' => 'Gerenciar',
        'export' => 'Exportar',
        'import' => 'Importar',
        'approve' => 'Aprovar',
        'cancel' => 'Cancelar',
        'reschedule' => 'Remarcar',
    ];
@endphp

<div class="grid gap-4 md:grid-cols-12">
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Nome do perfil</label>
        <input class="{{ $input }}" id="name" name="name" value="{{ old('name', $role->name ?? '') }}" required />
        <x-input-error class="mt-1" :messages="$errors->get('name')" />
    </div>
    <div class="md:col-span-6">
        <label class="mb-1 block text-sm font-medium text-gray-700" for="description">Descricao</label>
        <input class="{{ $input }}" id="description" name="description" value="{{ old('description', $role->description ?? '') }}" />
        <x-input-error class="mt-1" :messages="$errors->get('description')" />
    </div>
    <div class="md:col-span-12">
        @php $isDefault = old('is_default', $role->is_default ?? false); @endphp
        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="is_default" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($isDefault) />
            Perfil padrao da empresa
        </label>
    </div>
</div>

<div class="mt-6 space-y-4">
    <div class="text-sm font-semibold text-gray-800">Permissoes</div>
    @foreach ($grouped as $module => $resources)
        <div class="rounded-lg border border-gray-200 p-4">
            <div class="mb-3 text-sm font-semibold text-gray-700">{{ ucfirst($module) }}</div>
            <div class="space-y-3">
                @foreach ($resources as $resource => $items)
                    <div>
                        <div class="mb-2 text-xs font-semibold uppercase text-gray-500">{{ ucfirst($resource) }}</div>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($items as $permission)
                                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked(in_array($permission->id, $selected ?? [])) />
                                    {{ $actionLabels[$permission->action] ?? ucfirst($permission->action) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
