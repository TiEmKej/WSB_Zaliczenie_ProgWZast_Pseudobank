@extends('layouts.app')

@section('title', 'Moje konto')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Moje konto</h1>

    <div class="rounded bg-white p-6 shadow">
        <p class="text-sm text-slate-500">Numer konta</p>
        <p class="mb-4 font-mono">{{ $user->account_number }}</p>

        <p class="text-sm text-slate-500">Saldo</p>
        <p class="text-3xl font-semibold">{{ number_format($user->balance, 2, ',', ' ') }} zł</p>
    </div>
@endsection
