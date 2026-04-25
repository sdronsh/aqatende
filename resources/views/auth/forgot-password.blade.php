<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Esqueceu sua senha? Informe seu email e enviaremos um link para redefinir.
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="email">Email</label>
            <input id="email" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10" type="email" name="email" value="{{ old('email') }}" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="flex justify-end">
            <x-primary-button>
                Enviar link
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
