<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Basic Academy Login') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1f2937 0%, #0d121c 100%);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-blue': '#3b82f6',
                        'dark-accent': '#1e3a8a',
                        'accent-yellow': '#fcd34d',
                        'logo-yellow': '#facc15',
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    @php
        $status = session('status');
        $hasErrors = $errors->any();
    @endphp

    <div id="messageBox" class="fixed top-5 left-1/2 -translate-x-1/2 w-full max-w-md z-50 {{ ($status || $hasErrors) ? '' : 'hidden' }}">
        @if ($hasErrors)
            <div class="p-4 bg-red-500 text-white rounded-lg shadow-xl space-y-1">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @elseif ($status)
            <div class="p-4 bg-green-500 text-white rounded-lg shadow-xl flex items-center justify-between">
                <span>{{ $status }}</span>
                <button onclick="document.getElementById('messageBox').classList.add('hidden')" class="font-bold ml-4">&times;</button>
            </div>
        @endif
    </div>

    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl p-8 space-y-8 md:p-10 transition-all duration-300">
        <div class="text-center space-y-3">
            <img 
                src="{{ asset('images/basic.jpg') }}"
                alt="Basic Academy Logo"
                class="w-32 h-32 mx-auto object-contain"
            >

            <h1 class="text-3xl font-extrabold text-gray-900">
                Welcome to Basic Academy
            </h1>
            <p class="text-lg font-semibold text-dark-accent">
                Only For HSC
            </p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="student_login" value="1">
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Mobile Number
                </label>
                <input
                    type="text"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    placeholder="01XXXXXXXXX"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-blue focus:border-primary-blue transition duration-150 ease-in-out"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    Password
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="••••••••"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-blue focus:border-primary-blue transition duration-150 ease-in-out"
                >
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-primary-blue border-gray-300 rounded focus:ring-primary-blue">
                    <label for="remember" class="ml-2 block text-sm text-gray-900">
                        Remember me
                    </label>
                </div>

                @if (Route::has('password.request'))
                    <div class="text-sm">
                        <a href="{{ route('password.request') }}" class="font-medium text-primary-blue hover:text-blue-700 transition-colors duration-150">
                            Forgot your password?
                        </a>
                    </div>
                @endif
            </div>

            <div>
                <button
                    type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-lg font-bold text-white bg-primary-blue hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-blue transition duration-150 ease-in-out transform hover:scale-[1.01]"
                >
                    Sign In
                </button>
            </div>
        </form>
    </div>
</body>
</html>
