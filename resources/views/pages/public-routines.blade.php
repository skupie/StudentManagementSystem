<x-guest-layout>
    <div class="max-w-6xl mx-auto py-8 px-4 space-y-6">
        <div class="text-center space-y-2">
            <h1 class="text-2xl font-bold text-gray-800">Class Routines</h1>
            <p class="text-sm text-gray-600">Select a date to view the routine for that day. No login required.</p>
        </div>

        @livewire('routines.routine-viewer')
    </div>
</x-guest-layout>
