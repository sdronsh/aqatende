<section>
    <header class="mb-4">
        <h2 class="text-base font-semibold text-error-600">Excluir conta</h2>
        <p class="text-sm text-gray-500">Esta acao remove sua conta e todos os dados relacionados.</p>
    </header>

    <form method="post" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Tem certeza que deseja excluir a conta?');" class="space-y-4">
        @csrf
        @method('delete')

        <div>
            <x-input-label for="password" :value="__('Senha')" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
        </div>

        <div>
            <x-danger-button>Excluir conta</x-danger-button>
        </div>
    </form>
</section>
