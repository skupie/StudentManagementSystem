<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Teacher Portal') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-4">
                <a href="{{ route('weekly-exams.index') }}" class="bg-white shadow rounded-lg p-4 block hover:bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-800">Exams</h3>
                    <p class="text-sm text-gray-500">Update weekly exam marks and results.</p>
                </a>
                <a href="{{ route('class.notes.index') }}" class="bg-white shadow rounded-lg p-4 block hover:bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-800">Lecture Notes</h3>
                    <p class="text-sm text-gray-500">Upload and manage notes by class and section.</p>
                </a>
                <a href="{{ route('teacher.transactions') }}" class="bg-white shadow rounded-lg p-4 block hover:bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-800">Transaction Log</h3>
                    <p class="text-sm text-gray-500">View monthly payment entries recorded to ledger.</p>
                </a>
            </div>
        </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:teachers.teacher-routine-table />
        </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:teachers.weekly-exam-pending-alert />
        </div>
    </div>
</x-app-layout>
