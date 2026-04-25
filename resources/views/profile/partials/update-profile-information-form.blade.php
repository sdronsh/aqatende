<section>
    <header class="mb-4">
        <h2 class="text-base font-semibold text-gray-800">Informacoes do perfil</h2>
        <p class="text-sm text-gray-500">Atualize seu nome e e-mail de acesso.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Nome')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $user->username)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    <div>Seu e-mail ainda nao foi verificado.</div>
                    <button form="send-verification" class="mt-2 inline-flex items-center text-sm font-semibold text-amber-800 hover:text-amber-900">Reenviar e-mail de verificacao</button>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <x-primary-button>Salvar</x-primary-button>
        </div>
    </form>
</section>
