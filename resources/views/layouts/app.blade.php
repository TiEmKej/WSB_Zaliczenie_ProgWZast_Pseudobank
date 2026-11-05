<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Pseudobank')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <nav class="flex items-center justify-between bg-white px-6 py-4 shadow">
        <a href="/" class="text-xl font-semibold">Pseudobank</a>
        <div class="flex items-center gap-4">
            @auth
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-blue-600 hover:underline">Wyloguj</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Logowanie</a>
                <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Rejestracja</a>
            @endauth
        </div>
    </nav>

    <main class="mx-auto @yield('width', 'max-w-md') p-6">
        @if (session('status'))
            <div class="mb-4 rounded bg-green-100 p-3 text-green-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded bg-red-100 p-3 text-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
