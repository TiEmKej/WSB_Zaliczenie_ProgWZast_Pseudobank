@extends('layouts.app')

@section('title', 'Rejestracja')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Rejestracja</h1>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="block">Imię i nazwisko</label>
            <input id="name" name="name" value="{{ old('name') }}" class="w-full rounded border p-2">
        </div>
        <div>
            <label for="email" class="block">E-mail</label>
            <input id="email" name="email" value="{{ old('email') }}" class="w-full rounded border p-2">
        </div>
        @include('partials.pesel-fields', ['user' => new App\Models\User])
        <div>
            <label for="password" class="block">Hasło</label>
            <input id="password" type="password" name="password" class="w-full rounded border p-2">
        </div>
        <div>
            <label for="password_confirmation" class="block">Powtórz hasło</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="w-full rounded border p-2">
        </div>
        <button class="w-full rounded bg-blue-600 p-2 text-white hover:bg-blue-700">Zarejestruj się</button>
    </form>
@endsection
