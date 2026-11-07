@extends('layouts.app')

@section('title', 'Rejestracja')
@section('width', 'max-w-xl')

@section('content')
    <h1 class="page-title">Rejestracja</h1>
    <p class="mb-6 mt-2 text-stone-600">Założysz konto z saldem startowym 1 000,00 zł. Link aktywacyjny wyślemy na Twój e-mail.</p>

    <form method="POST" action="{{ route('register') }}" class="card grid gap-5 sm:grid-cols-2">
        @csrf
        <x-field name="name" label="Imię i nazwisko" :value="old('name')" />
        <x-field name="email" label="E-mail" :value="old('email')" />
        @include('partials.pesel-fields', ['user' => new App\Models\User])
        <x-field name="password" label="Hasło" type="password" />
        <x-field name="password_confirmation" label="Powtórz hasło" type="password" />
        <button class="btn btn-primary btn-block sm:col-span-2">Zarejestruj się</button>
    </form>

    <p class="mt-6 text-center text-stone-600">Masz już konto? <a href="{{ route('login') }}" class="link">Zaloguj się</a></p>
@endsection
