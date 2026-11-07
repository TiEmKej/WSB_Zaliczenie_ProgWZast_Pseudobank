<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Pseudobank')</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-100 font-sans text-emerald-950 antialiased">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-x-4 px-4 pt-1 sm:px-6 sm:py-2.5">
            <a href="/" class="inline-flex min-h-11 items-center gap-2.5" aria-label="Pseudobank – strona główna">
                <x-logo />
                <span class="text-[22px] leading-none tracking-tighter"><span class="font-normal">pseudo</span><span class="font-bold">bank</span></span>
            </a>

            <nav aria-label="Główna nawigacja" class="order-last flex basis-full gap-2 overflow-x-auto pb-3 sm:order-none sm:ml-auto sm:basis-auto sm:items-center sm:gap-1 sm:overflow-visible sm:pb-0">
                @auth
                    @if (auth()->user()->hasVerifiedEmail())
                        @if (auth()->user()->role === 'user')
                            <a href="{{ route('transfers.index') }}" class="nav-link" @if (request()->routeIs('transfers.*')) aria-current="page" @endif>Przelewy</a>
                            <a href="{{ route('recipients.index') }}" class="nav-link" @if (request()->routeIs('recipients.*')) aria-current="page" @endif>Odbiorcy</a>
                        @endif
                        <a href="{{ route('profile') }}" class="nav-link" @if (request()->routeIs('profile')) aria-current="page" @endif>{{ auth()->user()->name }}</a>
                    @else
                        <span class="inline-flex min-h-11 items-center px-3">{{ auth()->user()->name }}</span>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="nav-link" @if (request()->routeIs('login')) aria-current="page" @endif>Logowanie</a>
                    <a href="{{ route('register') }}" class="nav-link sm:border sm:border-stone-500" @if (request()->routeIs('register')) aria-current="page" @endif>Rejestracja</a>
                @endauth
            </nav>

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="inline-flex min-h-11 items-center font-medium text-emerald-800 underline sm:min-h-10 sm:rounded-[10px] sm:border sm:border-stone-500 sm:px-3 sm:text-emerald-950 sm:no-underline sm:hover:bg-stone-100">Wyloguj</button>
                </form>
            @endauth
        </div>
    </header>

    <main class="mx-auto @yield('width', 'max-w-md') px-4 py-6 sm:px-6 sm:py-10">
        @if (session('status'))
            <div class="alert alert-success mb-6" role="status">
                <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="10" r="8"/><path d="m6.5 10.5 2.5 2.5 4.5-5"/></svg>
                <p>{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error mb-6" role="alert">
                <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="10" cy="10" r="8"/><path d="M10 6v5M10 14h.01"/></svg>
                <div>
                    <p class="font-semibold">Popraw pola formularza:</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->keys() as $key)
                            <li><a href="#{{ $key }}" class="underline">{{ $errors->first($key) }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
