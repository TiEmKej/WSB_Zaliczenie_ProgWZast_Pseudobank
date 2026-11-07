@extends('layouts.app')

@section('title', 'Mój profil')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Mój profil</h1>

    <dl class="space-y-2 rounded bg-white p-6 shadow">
        <div><dt class="text-sm text-slate-500">Imię i nazwisko</dt><dd>{{ $user->name }}</dd></div>
        <div><dt class="text-sm text-slate-500">E-mail</dt><dd>{{ $user->email }}</dd></div>
        <div><dt class="text-sm text-slate-500">PESEL</dt><dd class="font-mono">{{ $user->pesel }}</dd></div>
        <div><dt class="text-sm text-slate-500">Data urodzenia</dt><dd>{{ $user->birthDate()?->format('d.m.Y') }}</dd></div>
        <div><dt class="text-sm text-slate-500">Płeć</dt><dd>{{ $user->genderLabel() }}</dd></div>
        <div><dt class="text-sm text-slate-500">Rola</dt><dd>{{ $user->roleLabel() }}</dd></div>
        @unless ($user->isAdmin())
            <div><dt class="text-sm text-slate-500">Numer konta</dt><dd class="font-mono">{{ $user->formattedAccountNumber() }}</dd></div>
            <div><dt class="text-sm text-slate-500">Saldo</dt><dd>{{ number_format($user->balance, 2, ',', ' ') }} zł</dd></div>
        @endunless
        <div><dt class="text-sm text-slate-500">Konto aktywowane</dt><dd>{{ $user->email_verified_at?->format('d.m.Y H:i') }}</dd></div>
        <div><dt class="text-sm text-slate-500">Konto założone</dt><dd>{{ $user->created_at->format('d.m.Y H:i') }}</dd></div>
    </dl>
@endsection
