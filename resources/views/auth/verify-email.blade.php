<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Obrigado por se cadastrar! Verifique seu email clicando no link que enviamos.
    </div>

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                Reenviar email
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-secondary-button>
                Sair
            </x-secondary-button>
        </form>
    </div>
</x-guest-layout>
