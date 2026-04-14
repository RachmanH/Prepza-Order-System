@props(['disabled' => false])

<input
    {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge([
        'class' => 'input-base' . ($disabled ? ' cursor-not-allowed bg-slate-50 text-slate-400' : '')
    ]) !!}
>
