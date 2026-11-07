@extends('layouts.app')

@section('title', 'Aktywacja konta')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Aktywuj konto</h1>
    <p class="mb-4">Wysłaliśmy link aktywacyjny na adres {{ auth()->user()->email }}. Kliknij go, aby korzystać z konta.</p>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="w-full rounded bg-blue-600 p-2 text-white hover:bg-blue-700">Wyślij link ponownie</button>
    </form>
@endsection
