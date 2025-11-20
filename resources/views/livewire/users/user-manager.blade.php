<div class="space-y-6">
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
                    <option value="instructor">Lead Instructor</option>
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
                                {{ $user->role === 'admin' ? 'System Admin' : ($user->role === 'director' ? 'Director' : 'Lead Instructor') }}
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
</div>
