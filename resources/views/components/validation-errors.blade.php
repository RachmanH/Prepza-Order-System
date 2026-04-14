@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-red-200 bg-red-50 p-4']) }}>
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <div>
                <p class="text-sm font-semibold text-red-700">{{ __('Ada beberapa masalah dengan input Anda.') }}</p>
                <ul class="mt-1.5 space-y-1 text-sm text-red-600 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
