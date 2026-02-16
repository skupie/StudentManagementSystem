<div
    x-data="{ show: false, message: '', timer: null }"
    x-on:notify.window="
        message = $event.detail?.message || 'Student credentials updated.';
        show = true;
        clearTimeout(timer);
        timer = setTimeout(() => { show = false; }, 2500);
    "
    class="space-y-6"
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
    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <h3 class="font-semibold text-gray-800">Student Login Credentials</h3>
        <p class="text-sm text-gray-500">Create or update student passwords. Students log in using their contact number.</p>
        <div class="grid md:grid-cols-4 gap-3">
            <div>
                <x-input-label value="Search" />
                <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="Name or mobile" />
            </div>
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
                <x-input-label value="Account Status" />
                <select wire:model.live="linkFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="all">All</option>
                    <option value="linked">Linked</option>
                    <option value="unlinked">Not Linked</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <h4 class="font-semibold text-gray-800">Set Password</h4>
        <div class="grid md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <x-input-label value="Student" />
                <select wire:model.live="selectedStudentId" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">Select student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->phone_number ?? 'No mobile' }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('selectedStudentId')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Password" />
                <x-text-input type="password" wire:model.defer="form.password" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('form.password')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Confirm Password" />
                <x-text-input type="password" wire:model.defer="form.password_confirmation" class="mt-1 block w-full" />
            </div>
        </div>
        <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model.defer="form.is_active" class="rounded border-gray-300 text-indigo-600">
                Keep account active
            </label>
            <x-primary-button type="button" wire:click="saveCredentials">
                Save Credentials
            </x-primary-button>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <h4 class="font-semibold text-gray-800">Bulk Reset</h4>
        <p class="text-sm text-gray-500">Set Default Password to <span class="font-semibold">basic123</span></p>
        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <x-input-label value="Confirmation PIN" />
                <x-text-input type="password" wire:model.defer="bulkPin" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('bulkPin')" class="mt-1" />
            </div>
            <div class="md:col-span-2 flex items-end justify-end gap-2">
                <x-secondary-button type="button" wire:click="voidFilteredPasswords">
                    Void Default Password
                </x-secondary-button>
                <x-danger-button type="button" wire:click="resetFilteredPasswordsToDefault">
                    Set Default Password
                </x-danger-button>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <h4 class="font-semibold text-gray-800">Students</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-3 py-2">Student</th>
                        <th class="px-3 py-2">Class</th>
                        <th class="px-3 py-2">Mobile</th>
                        <th class="px-3 py-2">Login</th>
                        <th class="px-3 py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($students as $student)
                        @php($account = $accountsByStudent[$student->id] ?? null)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-semibold text-gray-900">{{ $student->name }}</td>
                            <td class="px-3 py-2">{{ \App\Support\AcademyOptions::classLabel($student->class_level) }} / {{ \App\Support\AcademyOptions::sectionLabel($student->section) }}</td>
                            <td class="px-3 py-2">{{ $student->phone_number ?? 'N/A' }}</td>
                            <td class="px-3 py-2">
                                @if ($account)
                                    <span class="px-2 py-1 rounded-full text-xs {{ $account->is_active ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ $account->is_active ? 'Linked (Active)' : 'Not Linked' }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700">Not Linked</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <x-secondary-button type="button" wire:click="selectStudent({{ $student->id }})" class="text-xs">
                                    Select
                                </x-secondary-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-gray-500">No students found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $students->links() }}
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
