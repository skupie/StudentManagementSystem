<div
    x-data="{ show: false, message: '', timer: null }"
    x-on:notify.window="
        message = $event.detail?.message || 'Due alert sent successfully.';
        show = true;
        clearTimeout(timer);
        timer = setTimeout(() => { show = false; }, 2500);
    "
    class="bg-white shadow rounded-lg p-6 space-y-6"
>
    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="fixed inset-0 z-[1100] flex items-center justify-center pointer-events-none"
    >
        <div class="relative overflow-hidden rounded-xl border border-green-100 bg-white shadow-2xl pointer-events-auto">
            <div class="px-5 py-4 flex items-center gap-3">
                <div class="enroll-badge">
                    <svg viewBox="0 0 24 24" class="w-6 h-6 text-white">
                        <path fill="currentColor" d="M9.2 16.2l-3.4-3.4 1.4-1.4 2 2 6-6 1.4 1.4-7.4 7.4z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-semibold text-gray-900">Student Updated Seccuessfully</div>
                    <div class="text-xs text-gray-600" x-text="message"></div>
                </div>
            </div>
            <div class="enroll-glow"></div>
            <span class="sparkle s1"></span>
            <span class="sparkle s2"></span>
            <span class="sparkle s3"></span>
        </div>
    </div>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="grid md:grid-cols-4 gap-3 w-full md:w-auto">
            <div>
                <x-input-label value="Class" />
                <select wire:model.live="classFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($classOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Section" />
                <select wire:model.live="sectionFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($sectionOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Year" />
                <x-text-input type="text" wire:model.debounce.500ms="yearFilter" class="mt-1 block w-full" placeholder="2024" />
            </div>
            <div>
                <x-input-label value="Month (optional)" />
                <x-text-input type="month" wire:model.live="monthFilter" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label value="Student Name" />
                <x-text-input type="text" wire:model.live.debounce.300ms="nameFilter" class="mt-1 block w-full" placeholder="Search name" />
            </div>
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-500">Total Outstanding</div>
            <div class="text-2xl font-bold text-red-600">Tk {{ number_format($totalDue, 2) }}</div>
        </div>
    </div>

    <div class="flex justify-between items-center">
        <div class="text-sm text-gray-500">
            Showing {{ $students->count() }} students with dues
        </div>
        @unless ($embedded)
            <div class="space-x-2">
                <x-secondary-button type="button" wire:click="sendManualDueAlertAll">
                    Send Due Alert To All
                </x-secondary-button>
                <x-secondary-button type="button" wire:click="exportPdf">
                    Download PDF
                </x-secondary-button>
                <x-secondary-button type="button" wire:click="exportExcel">
                    Download Excel
                </x-secondary-button>
            </div>
        @endunless
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <th class="px-4 py-2">Student</th>
                    <th class="px-4 py-2">Section</th>
                    <th class="px-4 py-2">Outstanding (Tk)</th>
                    <th class="px-4 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($students as $student)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <div class="font-semibold text-gray-900">{{ $student->name }}</div>
                            <div class="text-xs text-gray-500">{{ $student->phone_number }}</div>
                        </td>
                        <td class="px-4 py-2">
                            {{ \App\Support\AcademyOptions::classLabel($student->class_level) }}
                            <div class="text-xs text-gray-500">{{ \App\Support\AcademyOptions::sectionLabel($student->section) }}</div>
                        </td>
                        <td class="px-4 py-2 text-red-600 font-semibold">Tk {{ number_format($student->outstanding, 2) }}</td>
                        <td class="px-4 py-2 text-right">
                            <div class="inline-flex flex-col sm:flex-row items-end gap-2">
                                <button
                                    type="button"
                                    wire:click="sendManualDueAlert({{ $student->id }})"
                                    class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-red-700 border border-red-200 bg-red-50 hover:bg-red-100"
                                >
                                    Send Due Alert
                                </button>
                                <a href="tel:{{ $student->phone_number }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-blue-700 border border-blue-200 hover:bg-blue-50">
                                    Call
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                            No dues found for the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .enroll-badge {
            width: 36px;
            height: 36px;
            border-radius: 9999px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            box-shadow: 0 10px 24px rgba(34, 197, 94, 0.35);
            animation: enroll-pop 320ms ease-out;
        }
        .enroll-glow {
            position: absolute;
            inset: 0;
            background: radial-gradient(120px 60px at 10% 0%, rgba(34, 197, 94, 0.18), transparent 60%);
            pointer-events: none;
            animation: enroll-glow 1400ms ease-out;
        }
        .sparkle {
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 9999px;
            background: #86efac;
            opacity: 0;
            animation: sparkle 1200ms ease-out;
        }
        .sparkle.s1 { top: 8px; right: 18px; animation-delay: 80ms; }
        .sparkle.s2 { top: 20px; right: 6px; animation-delay: 160ms; }
        .sparkle.s3 { top: 34px; right: 28px; animation-delay: 240ms; }

        @keyframes enroll-pop {
            0% { transform: scale(0.7); }
            100% { transform: scale(1); }
        }
        @keyframes enroll-glow {
            0% { opacity: 0; transform: translateY(-6px); }
            40% { opacity: 1; }
            100% { opacity: 0; transform: translateY(8px); }
        }
        @keyframes sparkle {
            0% { opacity: 0; transform: translateY(0) scale(0.6); }
            30% { opacity: 1; }
            100% { opacity: 0; transform: translateY(-12px) scale(1.1); }
        }
    </style>
</div>
