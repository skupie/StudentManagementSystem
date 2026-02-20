<div
    x-data="{
        title: @entangle('form.title'),
        body: @entangle('form.body'),
        date: @entangle('form.notice_date'),
        selectedSize: '16',
        wrapSelection(openTag, closeTag) {
            const editor = this.$refs.bodyEditor;
            if (!editor) return;
            const start = editor.selectionStart ?? 0;
            const end = editor.selectionEnd ?? 0;
            if (end <= start) return;

            const value = editor.value || '';
            const selected = value.substring(start, end);
            this.body = value.slice(0, start) + openTag + selected + closeTag + value.slice(end);

            this.$nextTick(() => {
                editor.focus();
                editor.setSelectionRange(start + openTag.length, start + openTag.length + selected.length);
            });
        },
        applyBold() { this.wrapSelection('<strong>', '</strong>'); },
        applyItalic() { this.wrapSelection('<em>', '</em>'); },
        applyUnderline() { this.wrapSelection('<u>', '</u>'); },
        applyFontSize() {
            const size = parseInt(this.selectedSize || '16', 10);
            if (!size) return;
            this.wrapSelection('<span style=\'font-size: ' + size + 'px;\'>', '</span>');
        }
    }"
    class="space-y-6"
>
    <div class="rounded-2xl bg-gradient-to-r from-indigo-600 via-blue-500 to-teal-500 p-6 text-white shadow-lg" style="background: linear-gradient(90deg, #4f46e5 0%, #3b82f6 50%, #14b8a6 100%);">
        <h3 class="text-2xl font-bold tracking-tight">Student Notice Board</h3>
        <p class="text-sm text-blue-50 mt-1">Create vibrant announcements and control what students see on login.</p>
        <div class="grid md:grid-cols-3 gap-3 mt-4">
            <div class="rounded-xl px-4 py-3" style="background-color: rgba(255,255,255,0.18);">
                <div class="text-xs uppercase tracking-wider text-blue-100">Total Notices</div>
                <div class="text-2xl font-bold">{{ $totalCount }}</div>
            </div>
            <div class="rounded-xl px-4 py-3" style="background-color: rgba(255,255,255,0.18);">
                <div class="text-xs uppercase tracking-wider text-blue-100">Active</div>
                <div class="text-2xl font-bold">{{ $activeCount }}</div>
            </div>
            <div class="rounded-xl px-4 py-3" style="background-color: rgba(255,255,255,0.18);">
                <div class="text-xs uppercase tracking-wider text-blue-100">Today</div>
                <div class="text-2xl font-bold">{{ $todayCount }}</div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6 space-y-4 border border-blue-100">
            <div class="flex items-center justify-between">
                <h4 class="text-lg font-semibold text-gray-800">{{ $editingId ? 'Edit Notice' : 'Publish New Notice' }}</h4>
                <span class="text-xs px-2 py-1 rounded-full {{ $form['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $form['is_active'] ? 'Visible to Students' : 'Draft / Hidden' }}
                </span>
            </div>

            <form wire:submit.prevent="save" class="grid md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <x-input-label value="Title" />
                    <x-text-input type="text" wire:model.defer="form.title" class="mt-1 block w-full border-blue-200 focus:border-blue-400 focus:ring-blue-400" placeholder="Exam Schedule Update" />
                    <x-input-error :messages="$errors->get('form.title')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Notice Date" />
                    <x-text-input type="date" wire:model.defer="form.notice_date" class="mt-1 block w-full border-blue-200 focus:border-blue-400 focus:ring-blue-400" />
                    <x-input-error :messages="$errors->get('form.notice_date')" class="mt-1" />
                </div>

                <div class="md:col-span-3">
                    <x-input-label value="Notice Body" />
                    <div class="mt-1 rounded-md border border-blue-200 bg-blue-50/40 p-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" class="px-3 py-1 rounded border border-blue-200 bg-white text-sm font-semibold text-gray-700 hover:bg-blue-100" @click="applyBold()">
                                Bold
                            </button>
                            <button type="button" class="px-3 py-1 rounded border border-blue-200 bg-white text-sm italic text-gray-700 hover:bg-blue-100" @click="applyItalic()">
                                Italic
                            </button>
                            <button type="button" class="px-3 py-1 rounded border border-blue-200 bg-white text-sm underline text-gray-700 hover:bg-blue-100" @click="applyUnderline()">
                                Underline
                            </button>
                            <div class="flex items-center gap-2 ml-auto">
                                <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Font size</label>
                                <select x-model="selectedSize" class="rounded border-blue-200 text-sm focus:border-blue-400 focus:ring-blue-400">
                                    <option value="12">12px</option>
                                    <option value="14">14px</option>
                                    <option value="16">16px</option>
                                    <option value="18">18px</option>
                                    <option value="20">20px</option>
                                    <option value="24">24px</option>
                                    <option value="28">28px</option>
                                    <option value="32">32px</option>
                                </select>
                                <button type="button" class="px-3 py-1 rounded border border-blue-200 bg-white text-sm font-semibold text-gray-700 hover:bg-blue-100" @click="applyFontSize()">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>
                    <textarea x-ref="bodyEditor" x-model="body" wire:model.defer="form.body" class="mt-2 block w-full rounded-md border-blue-200 focus:border-blue-400 focus:ring-blue-400 font-mono text-sm" rows="7" placeholder="Write your notice details here... Select text and use toolbar to style it."></textarea>
                    <div class="text-xs text-gray-400 mt-1">Characters: <span x-text="(body || '').length"></span></div>
                    <div class="text-xs text-gray-500 mt-1">Tip: select text first, then click Bold/Italic/Underline or Apply font size.</div>
                    <x-input-error :messages="$errors->get('form.body')" class="mt-1" />
                </div>

                <div class="md:col-span-3 border-t border-gray-100 pt-4 mt-1">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <label class="inline-flex items-start gap-2 text-sm text-gray-700 leading-relaxed">
                            <input type="checkbox" wire:model.defer="form.is_active" class="mt-0.5 rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500" />
                            <span>Active (show this as popup to students)</span>
                        </label>

                        <div class="flex items-center justify-end gap-3">
                            @if ($editingId)
                                <x-secondary-button type="button" wire:click="resetForm">Cancel</x-secondary-button>
                            @endif
                            <x-primary-button type="submit">{{ $editingId ? 'Update Notice' : 'Publish Notice' }}</x-primary-button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white shadow rounded-xl p-6 border border-yellow-100">
            <h4 class="text-lg font-semibold text-gray-800">Live Preview</h4>
            <div class="mt-3 rounded-xl border border-yellow-200 bg-gradient-to-br from-yellow-50 to-orange-50 p-4 space-y-2">
                <div class="text-xs text-yellow-700 font-semibold">Student Popup</div>
                <div class="text-base font-semibold text-gray-900" x-text="title || 'Notice title will appear here'"></div>
                <div class="text-xs text-gray-500">Date: <span x-text="date || 'Select a date'"></span></div>
                <div
                    class="text-sm text-gray-700 leading-relaxed break-words"
                    x-html="(body && body.trim() !== '') ? body : '<span class=&quot;text-gray-500&quot;>Notice details preview...</span>'"
                ></div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-xl p-6 border border-indigo-100 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div class="md:col-span-2 min-w-0">
                <x-input-label value="Search Notices" />
                <x-text-input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block w-full border-indigo-200 focus:border-indigo-400 focus:ring-indigo-400" placeholder="Search by title or body..." />
            </div>
            <div class="md:col-span-1">
                <x-input-label value="Status" />
                <select wire:model.live="statusFilter" class="mt-1 block w-full rounded-md border-indigo-200 focus:border-indigo-400 focus:ring-indigo-400">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="grid gap-3">
            @forelse ($notices as $notice)
                <div class="rounded-xl border {{ $notice->is_active ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }} p-4">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h5 class="font-semibold text-gray-900">{{ $notice->title }}</h5>
                                <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ $notice->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $notice->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">
                                {{ optional($notice->notice_date)->format('d M Y') }} | by {{ $notice->creator?->name ?? '-' }}
                            </p>
                            <p class="text-xs font-medium text-indigo-700">
                                Acknowledged by {{ (int) ($notice->acknowledged_count ?? 0) }} student{{ ((int) ($notice->acknowledged_count ?? 0)) === 1 ? '' : 's' }}
                            </p>
                            <p class="text-sm text-gray-700">{{ \Illuminate\Support\Str::limit(strip_tags($notice->body), 220) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-secondary-button type="button" class="text-xs" wire:click="toggleActive({{ $notice->id }})">
                                {{ $notice->is_active ? 'Deactivate' : 'Activate' }}
                            </x-secondary-button>
                            <x-secondary-button type="button" class="text-xs" wire:click="edit({{ $notice->id }})">Edit</x-secondary-button>
                            <x-danger-button type="button" class="text-xs" wire:click="promptDelete({{ $notice->id }})">Delete</x-danger-button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-gray-500">
                    No notices found for current filters.
                </div>
            @endforelse
        </div>

        <div>
            {{ $notices->links() }}
        </div>
    </div>

    @if (! is_null($confirmingDeleteId))
        <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Delete Notice</h3>
                    <button wire:click="cancelDelete" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-gray-700">
                    Delete <span class="font-semibold">{{ $confirmingDeleteTitle }}</span>?
                </p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancelDelete">Cancel</x-secondary-button>
                    <x-danger-button type="button" wire:click="delete({{ $confirmingDeleteId }})">Delete</x-danger-button>
                </div>
            </div>
        </div>
    @endif
</div>
