@props(['active'])

@php
$classes = ($active ?? false)
    ? 'flex items-center gap-2 w-full ps-4 pe-4 py-2.5 border-l-2 border-brand-500 text-sm font-semibold text-brand-700 bg-brand-50 transition duration-150'
    : 'flex items-center gap-2 w-full ps-4 pe-4 py-2.5 border-l-2 border-transparent text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-50 hover:border-slate-300 transition duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
