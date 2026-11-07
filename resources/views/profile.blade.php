@extends('layouts.app')

@section('title', 'Mój profil')
@section('width', 'max-w-2xl')

@section('content')
    <h1 class="page-title mb-6">Mój profil</h1>

    <p class="eyebrow mb-2">Dane osobowe</p>
    <dl class="data-list">
        <div class="data-row"><dt>Imię i nazwisko</dt><dd>{{ $user->name }}</dd></div>
        <div class="data-row"><dt>E-mail</dt><dd class="break-all">{{ $user->email }}</dd></div>
        <div class="data-row"><dt>PESEL</dt><dd class="font-mono tracking-wide">{{ $user->pesel }}</dd></div>
        <div class="data-row"><dt>Data urodzenia</dt><dd>{{ $user->birthDate()?->format('d.m.Y') }}</dd></div>
        <div class="data-row"><dt>Płeć</dt><dd>{{ $user->genderLabel() }}</dd></div>
    </dl>

    <p class="eyebrow mb-2 mt-8">Konto</p>
    <dl class="data-list">
        <div class="data-row"><dt>Rola</dt><dd>{{ $user->roleLabel() }}</dd></div>
        @unless ($user->isAdmin())
            <div class="data-row"><dt>Numer konta</dt><dd class="font-mono tracking-wide">{{ $user->formattedAccountNumber() }}</dd></div>
            <div class="data-row"><dt>Saldo</dt><dd class="font-semibold tabular-nums">{{ number_format($user->balance, 2, ',', ' ') }} zł</dd></div>
        @endunless
        <div class="data-row"><dt>Konto aktywowane</dt><dd class="tabular-nums">{{ $user->email_verified_at?->format('d.m.Y H:i') }}</dd></div>
        <div class="data-row"><dt>Konto założone</dt><dd class="tabular-nums">{{ $user->created_at->format('d.m.Y H:i') }}</dd></div>
    </dl>
@endsection
