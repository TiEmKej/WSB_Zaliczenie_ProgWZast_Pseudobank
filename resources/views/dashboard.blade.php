@extends('layouts.app')

@section('title', 'Moje konto')
@section('width', 'max-w-3xl')

@section('content')
    <h1 class="page-title mb-6">Moje konto</h1>

    <div class="flex flex-wrap items-end justify-between gap-6 rounded-2xl bg-emerald-950 p-5 text-stone-100 sm:p-7">
        <div>
            <p class="text-sm text-stone-400">Saldo</p>
            <p class="text-4xl font-semibold tabular-nums tracking-tight sm:text-5xl">{{ number_format($user->balance, 2, ',', ' ') }} zł</p>
            <p class="mt-4 text-sm text-stone-400">Numer konta</p>
            <p class="font-mono text-base tracking-wide sm:text-lg">{{ $user->formattedAccountNumber() }}</p>
        </div>
        <a href="{{ route('transfers.create') }}" class="btn btn-lime w-full min-h-12 px-6 sm:w-auto">Nowy przelew</a>
    </div>

    <div class="mb-3 mt-10 flex items-center justify-between gap-4">
        <h2 class="text-xl font-semibold">Ostatnie przelewy</h2>
        <a href="{{ route('transfers.index') }}" class="link-row">Cała historia</a>
    </div>

    @include('transfers._list', ['transfers' => $transfers, 'user' => $user])
@endsection
