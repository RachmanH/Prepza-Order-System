<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm
               transition duration-150 ease-in-out
               hover:bg-brand-700 hover:-translate-y-px hover:shadow-md
               active:translate-y-0 active:bg-brand-800
               focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2
               disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0'
]) }}>
    {{ $slot }}
</button>
