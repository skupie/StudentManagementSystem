<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Routines') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (in_array(auth()->user()?->role, ['teacher', 'lead_instructor']))
                @livewire('teachers.teacher-routine-table')
            @else
                @livewire('routines.routine-builder')
            @endif
        </div>
    </div>
</x-app-layout>
