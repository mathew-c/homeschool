<x-app-layout>
    <x-card header="Northstar Homeschool">
        <p class="text-sm text-slate-600">
            The daily board is powered by the Livewire homeschool cockpit route.
        </p>

        <x-slot:footer>
            <x-link href="{{ route('dashboard') }}" bold sm>Open the daily board</x-link>
        </x-slot:footer>
    </x-card>
</x-app-layout>
