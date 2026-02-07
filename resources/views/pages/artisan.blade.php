<x-guest-layout>
    <div class="max-w-3xl mx-auto py-10 px-4 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Artisan</h1>
            <p class="text-sm text-gray-600 mt-1">
                Run limited maintenance commands. This page is protected by a PIN.
            </p>
        </div>

        @if (session('artisan_error'))
            <div class="rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                {{ session('artisan_error') }}
            </div>
        @endif

        @if (session('artisan_status'))
            <div class="rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                {{ session('artisan_status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('artisan.run') }}" class="space-y-4">
            @csrf
            <div>
                <x-input-label value="PIN Code" />
                <x-text-input type="password" name="pin" class="mt-1 block w-full" required />
                @error('pin')
                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <x-input-label value="Command" />
                <select name="command" class="mt-1 block w-full rounded-md border-gray-300" required>
                    <option value="">Select a command</option>
                    @foreach ($commands as $value => $label)
                        <option value="{{ $value }}" @selected(old('command') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('command')
                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                @enderror
                <p class="text-xs text-gray-500 mt-1">Migrate runs with <span class="font-semibold">--force</span>.</p>
                <p class="text-xs text-red-600 mt-1">Warning: <span class="font-semibold">migrate:fresh</span> will drop all tables and data.</p>
            </div>

            <div class="text-right">
                <x-primary-button type="submit">Run Command</x-primary-button>
            </div>
        </form>

        @if (session('artisan_output'))
            <div class="rounded-md border border-gray-200 bg-white p-4">
                <div class="text-sm font-semibold text-gray-800 mb-2">Output</div>
                <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{ session('artisan_output') }}</pre>
            </div>
        @endif
    </div>
</x-guest-layout>
