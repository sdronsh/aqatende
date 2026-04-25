<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-lg bg-error-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-error-600']) }}>
    {{ $slot }}
</button>
