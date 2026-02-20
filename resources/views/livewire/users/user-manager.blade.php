<div
    x-data="{ show: false, message: '', timer: null }"
    x-on:user-notify.window="
        message = $event.detail?.message || 'Updated successfully.';
        show = true;
        clearTimeout(timer);
        timer = setTimeout(() => { show = false; }, 2600);
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
                    <div class="text-sm font-semibold text-gray-900">Action Completed</div>
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
        <h3 class="font-semibold text-gray-800">Create Team Member</h3>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <x-input-label value="Full Name" />
                <x-text-input type="text" wire:model.defer="form.name" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('form.name')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Email" />
                <x-text-input type="email" wire:model.defer="form.email" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('form.email')" class="mt-1" />
            </div>
        </div>
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <x-input-label value="Role" />
                <select wire:model.defer="form.role" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="admin">System Admin</option>
                    <option value="director">Director</option>
                    <option value="teacher">Teacher</option>
                    <option value="instructor">Instructor (Legacy)</option>
                    <option value="assistant">Administrative Assistant</option>
                </select>
                <x-input-error :messages="$errors->get('form.role')" class="mt-1" />
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
        <div class="text-right">
            <x-primary-button type="button" wire:click="save">
                Create User
            </x-primary-button>
        </div>
    </div>

    @if (auth()->user()?->role === 'admin')
        <div class="bg-white shadow rounded-lg p-4 space-y-4">
            <h3 class="font-semibold text-gray-800">Transfer PIN</h3>
            <p class="text-sm text-gray-600">Reset the PIN required to access the Transfer page. (Admin only)</p>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="New PIN" />
                    <x-text-input type="password" wire:model.defer="pinReset" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('pinReset')" class="mt-1" />
                </div>
            </div>
            <div class="text-right">
                <x-primary-button type="button" wire:click="resetTransferPin">
                    Update Transfer PIN
                </x-primary-button>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-4 space-y-4">
            <h3 class="font-semibold text-gray-800">Artisan PIN</h3>
            <p class="text-sm text-gray-600">Reset the PIN required to access the Artisan page. (Admin only)</p>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="New PIN" />
                    <x-text-input type="password" wire:model.defer="artisanPinReset" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('artisanPinReset')" class="mt-1" />
                </div>
            </div>
            <div class="text-right">
                <x-primary-button type="button" wire:click="resetArtisanPin">
                    Update Artisan PIN
                </x-primary-button>
            </div>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <h3 class="font-semibold text-gray-800">Members</h3>
            <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full md:w-64" placeholder="Search name or email" />
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2">Role</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Joined</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <div class="font-semibold text-gray-900">{{ $user->name }}</div>
                            </td>
                            <td class="px-4 py-2">{{ $user->email }}</td>
                            <td class="px-4 py-2">
                                @php
                                    $roleLabels = [
                                        'admin' => 'System Admin',
                                        'director' => 'Director',
                                        'teacher' => 'Teacher',
                                        'instructor' => 'Instructor (Legacy)',
                                        'lead_instructor' => 'Lead Instructor',
                                        'assistant' => 'Administrative Assistant',
                                    ];
                                @endphp
                                {{ $roleLabels[$user->role] ?? ucfirst($user->role) }}
                            </td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded-full text-xs {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ optional($user->created_at)->format('d M Y') }}</td>
                            <td class="px-4 py-2 text-right">
                                @if ($user->id !== auth()->id())
                                    <x-secondary-button type="button" wire:click="toggleStatus({{ $user->id }})" class="text-xs">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </x-secondary-button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </div>
    <style>
        [x-cloak] { display: none !important; }
        .enroll-badge {
            width: 36px;
            height: 36px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            animation: enroll-pop 420ms ease-out;
            box-shadow: 0 10px 24px rgba(34, 197, 94, 0.35);
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
            0% { transform: scale(0.7); opacity: 0; }
            60% { transform: scale(1.05); opacity: 1; }
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
