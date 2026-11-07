@extends('layouts.app')

@section('title', 'Moje konto')
@section('width', 'max-w-3xl')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Moje konto</h1>

    <div class="rounded bg-white p-6 shadow">
        <p class="text-sm text-slate-500">Numer konta</p>
        <p class="mb-4 font-mono">{{ $user->formattedAccountNumber() }}</p>

        <p class="text-sm text-slate-500">Saldo</p>
        <p class="text-3xl font-semibold">{{ number_format($user->balance, 2, ',', ' ') }} zł</p>
    </div>

    <a href="{{ route('transfers.create') }}" class="mt-4 inline-block rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Nowy przelew</a>

    <div class="mt-8 mb-2 flex items-center justify-between">
        <h2 class="text-xl font-semibold">Ostatnie przelewy</h2>
        <a href="{{ route('transfers.index') }}" class="text-blue-600 hover:underline">Cała historia</a>
    </div>

    @include('transfers._list', ['transfers' => $transfers, 'user' => $user])
@endsection
