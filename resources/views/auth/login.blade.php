<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Log in — {{ config('app.name', 'Organett') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] flex p-6 lg:p-8 items-center justify-center min-h-screen font-sans antialiased">

        <div class="w-full max-w-sm">

            <!-- Logo / Brand -->
            <div class="mb-8 text-center">
                <a href="{{ url('/') }}" class="inline-block text-[#f53003] dark:text-[#FF4433] font-semibold text-2xl tracking-tight">
                    Organett
                </a>
                <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Sign in to your account</p>
            </div>

            <!-- Card -->
            <div class="bg-white dark:bg-[#161615] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-lg p-8">

                <!-- Session Status -->
                @if (session('status'))
                    <div class="mb-4 text-sm text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-md px-4 py-3">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email -->
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium mb-1.5">
                            Email address
                        </label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full px-3 py-2 text-sm bg-[#FDFDFC] dark:bg-[#0a0a0a] border border-[#19140035] dark:border-[#3E3E3A] rounded-md shadow-xs focus:outline-none focus:border-[#f53003] dark:focus:border-[#FF4433] transition placeholder-[#706f6c] dark:placeholder-[#A1A09A] @error('email') border-red-400 dark:border-red-600 @enderror"
                            placeholder="you@example.com"
                        >
                        @error('email')
                            <p class="mt-1.5 text-xs text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-sm font-medium">
                                Password
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] transition">
                                    Forgot password?
                                </a>
                            @endif
                        </div>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="w-full px-3 py-2 text-sm bg-[#FDFDFC] dark:bg-[#0a0a0a] border border-[#19140035] dark:border-[#3E3E3A] rounded-md shadow-xs focus:outline-none focus:border-[#f53003] dark:focus:border-[#FF4433] transition @error('password') border-red-400 dark:border-red-600 @enderror"
                            placeholder="••••••••"
                        >
                        @error('password')
                            <p class="mt-1.5 text-xs text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center gap-2 mb-6">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4 rounded-sm border border-[#19140035] dark:border-[#3E3E3A] accent-[#f53003]"
                        >
                        <label for="remember_me" class="text-sm text-[#706f6c] dark:text-[#A1A09A] cursor-pointer select-none">
                            Remember me
                        </label>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        class="w-full px-5 py-2 text-sm font-medium text-white bg-[#1b1b18] dark:bg-[#EDEDEC] dark:text-[#1b1b18] rounded-md hover:bg-black dark:hover:bg-white transition active:opacity-80 cursor-pointer"
                    >
                        Log in
                    </button>
                </form>
            </div>

            <!-- Register link -->
            @if (Route::has('register'))
                <p class="mt-6 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-medium text-[#1b1b18] dark:text-[#EDEDEC] underline underline-offset-4 hover:text-[#f53003] dark:hover:text-[#FF4433] transition">
                        Register
                    </a>
                </p>
            @endif

        </div>

    </body>
</html>
