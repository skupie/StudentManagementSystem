<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-6 space-y-4">
        <h2 class="text-xl font-semibold text-gray-800">Manage Classes & Sections</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <div class="space-y-3">
                <h3 class="font-semibold text-gray-700">Add Class</h3>
                <div>
                    <x-input-label value="Key (e.g. hsc_1)" />
                    <x-text-input type="text" wire:model.defer="classKey" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('classKey')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Label" />
                    <x-text-input type="text" wire:model.defer="classLabel" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('classLabel')" class="mt-1" />
                </div>
                <div class="text-right">
                    <x-primary-button type="button" wire:click="saveClass">Save Class</x-primary-button>
                </div>
            </div>

            <div class="space-y-3">
                <h3 class="font-semibold text-gray-700">Add Section</h3>
                <div>
                    <x-input-label value="Key (e.g. science)" />
                    <x-text-input type="text" wire:model.defer="sectionKey" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('sectionKey')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Label" />
                    <x-text-input type="text" wire:model.defer="sectionLabel" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('sectionLabel')" class="mt-1" />
                </div>
                <div class="text-right">
                    <x-primary-button type="button" wire:click="saveSection">Save Section</x-primary-button>
                </div>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="font-semibold text-gray-800 mb-3">Classes</h3>
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-2 text-left">Key</th>
                        <th class="px-3 py-2 text-left">Label</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Toggle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($classes as $item)
                        <tr>
                            <td class="px-3 py-2">{{ $item->key }}</td>
                            <td class="px-3 py-2">{{ $item->label }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-1 text-xs rounded-full {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <x-secondary-button type="button" class="text-xs" wire:click="toggleClass({{ $item->id }})">
                                    Toggle
                                </x-secondary-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-4 text-center text-gray-500">No classes added yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="font-semibold text-gray-800 mb-3">Sections</h3>
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-2 text-left">Key</th>
                        <th class="px-3 py-2 text-left">Label</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Toggle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($sections as $item)
                        <tr>
                            <td class="px-3 py-2">{{ $item->key }}</td>
                            <td class="px-3 py-2">{{ $item->label }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-1 text-xs rounded-full {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <x-secondary-button type="button" class="text-xs" wire:click="toggleSection({{ $item->id }})">
                                    Toggle
                                </x-secondary-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-4 text-center text-gray-500">No sections added yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
