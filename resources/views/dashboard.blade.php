<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 rounded-xl border border-sky-200 bg-gradient-to-r from-sky-50 to-cyan-50 p-5 shadow-sm">
                <p class="text-sm text-sky-800">Frontend voice ordering siap dipakai.</p>
                <a href="{{ route('order.kiosk') }}" class="mt-3 inline-flex items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                    Buka Order Kiosk
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <x-welcome />
            </div>
        </div>
    </div>
</x-app-layout>
