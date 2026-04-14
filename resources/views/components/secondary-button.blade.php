<button {{ $attributes->merge([
    'type' => 'button',
    'class' => 'inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm
               transition duration-150 ease-in-out
               hover:bg-slate-50 hover:border-slate-300 hover:-translate-y-px
               active:translate-y-0 active:bg-slate-100
               focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2
               disabled:opacity-50 disabled:cursor-not-allowed'
]) }}>
    {{ $slot }}
</button>
