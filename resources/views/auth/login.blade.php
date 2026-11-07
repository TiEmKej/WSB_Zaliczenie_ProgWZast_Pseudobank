@extends('layouts.app')

@section('title', 'Logowanie')

@section('content')
    <h1 class="page-title">Logowanie</h1>
    <p class="mb-6 mt-2 text-stone-600">Zaloguj się, aby zobaczyć swoje konto i wykonać przelew.</p>

    <form method="POST" action="{{ route('login') }}" class="card space-y-5">
        @csrf
        <x-field name="email" label="E-mail" :value="old('email')" />
        <x-field name="password" label="Hasło" type="password" />
        <button class="btn btn-primary btn-block">Zaloguj się</button>
    </form>

    <p class="mt-6 text-center text-stone-600">Nie masz jeszcze konta? <a href="{{ route('register') }}" class="link">Zarejestruj się</a></p>
@endsection
