@extends('layouts.app')

@section('title', 'Logowanie')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Logowanie</h1>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block">E-mail</label>
            <input id="email" name="email" value="{{ old('email') }}" class="w-full rounded border p-2">
        </div>
        <div>
            <label for="password" class="block">Hasło</label>
            <input id="password" type="password" name="password" class="w-full rounded border p-2">
        </div>
        <button class="w-full rounded bg-blue-600 p-2 text-white hover:bg-blue-700">Zaloguj się</button>
    </form>
@endsection
