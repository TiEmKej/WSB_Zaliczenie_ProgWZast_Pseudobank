@extends('layouts.app')

@section('title', 'Potwierdzenie przelewu')
@section('width', 'max-w-2xl')

@section('content')
    <x-stepper :current="2" />

    <h1 class="page-title mb-6">Potwierdź przelew</h1>

    @include('transfers._source')

    <dl class="data-list">
        <div class="data-row"><dt>Numer konta odbiorcy</dt><dd class="font-mono tracking-wide">{{ App\Support\AccountNumber::format($pending['account_number']) }}</dd></div>
        <div class="data-row"><dt>Nazwa odbiorcy</dt><dd>{{ $pending['recipient_name'] }}</dd></div>
        <div class="data-row"><dt>Adres odbiorcy</dt><dd>{{ $pending['recipient_address'] }}</dd></div>
        <div class="data-row"><dt>Kwota</dt><dd class="text-xl font-semibold tabular-nums">{{ number_format($pending['amount'], 2, ',', ' ') }} zł</dd></div>
        <div class="data-row"><dt>Tytuł</dt><dd>{{ $pending['title'] }}</dd></div>
        <div class="data-row"><dt>Saldo po przelewie</dt><dd class="font-semibold tabular-nums">{{ number_format($user->balance - $pending['amount'], 2, ',', ' ') }} zł</dd></div>
    </dl>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row-reverse sm:justify-start">
        <form method="POST" action="{{ route('transfers.execute') }}" class="sm:w-auto">
            @csrf
            <button class="btn btn-primary btn-block sm:w-auto">Potwierdź</button>
        </form>
        <a href="{{ route('transfers.create') }}" class="btn btn-secondary btn-block sm:w-auto">Popraw</a>
    </div>
@endsection
