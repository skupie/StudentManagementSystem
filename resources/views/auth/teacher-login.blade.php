<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Teacher Login') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-slate-900">
    @php
        $status = session('status');
        $hasErrors = $errors->any();
    @endphp

    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl p-8 space-y-6">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-gray-900">Teacher Portal Login</h1>
            <p class="text-sm text-gray-500 mt-1">Sign in with your mobile number and password.</p>
        </div>

        @if ($hasErrors)
            <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @elseif ($status)
            <div class="p-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700">
                {{ $status }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                <input
                    type="text"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    placeholder="01XXXXXXXXX"
                    class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="inline-flex items-center gap-2 text-gray-600">
                    <input id="remember" name="remember" type="checkbox" class="rounded border-gray-300 text-indigo-600">
                    Remember me
                </label>
                <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-800">Staff Login</a>
            </div>

            <button type="submit" class="w-full py-2.5 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
                Sign In
            </button>
        </form>
    </div>
</body>
</html>
