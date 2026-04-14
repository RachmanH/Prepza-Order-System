<a {{ $attributes->merge([
    'class' => 'flex items-center gap-2 w-full px-4 py-2 text-sm text-slate-700 rounded-lg
               hover:bg-slate-50 hover:text-slate-900
               focus:outline-none focus:bg-slate-50
               transition duration-150 ease-in-out'
]) }}>{{ $slot }}</a>
