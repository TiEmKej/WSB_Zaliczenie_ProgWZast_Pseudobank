@extends('layouts.app')

@section('title', 'Potwierdzenie przelewu')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Potwierdź przelew</h1>

    <dl class="space-y-2 rounded bg-white p-6 shadow">
        <div><dt class="text-sm text-slate-500">Odbiorca</dt><dd>{{ $recipient?->name }}</dd></div>
        <div><dt class="text-sm text-slate-500">Numer konta</dt><dd class="font-mono">{{ App\Support\AccountNumber::format($pending['account_number']) }}</dd></div>
        <div><dt class="text-sm text-slate-500">Kwota</dt><dd>{{ number_format($pending['amount'], 2, ',', ' ') }} zł</dd></div>
        <div><dt class="text-sm text-slate-500">Tytuł</dt><dd>{{ $pending['title'] }}</dd></div>
    </dl>

    <div class="mt-4 flex items-center gap-4">
        <form method="POST" action="{{ route('transfers.execute') }}">
            @csrf
            <button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Potwierdź</button>
        </form>
        <a href="{{ route('transfers.create') }}" class="text-blue-600 hover:underline">Popraw</a>
    </div>
@endsection
