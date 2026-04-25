@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg bg-success-50 px-3 py-2 text-sm text-success-700']) }}>
        {{ $status }}
    </div>
@endif
