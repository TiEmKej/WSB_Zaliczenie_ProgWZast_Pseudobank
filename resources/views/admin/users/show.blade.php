@extends('layouts.app')

@section('title', $user->name)
@section('width', 'max-w-2xl')

@section('content')
    <a href="{{ route('admin.users.index') }}" class="link-row mb-2">← Wróć do listy</a>

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <h1 class="page-title">{{ $user->name }}</h1>
        <x-role-badge :user="$user" />
    </div>

    <dl class="data-list">
        <div class="data-row"><dt>E-mail</dt><dd class="break-all">{{ $user->email }}</dd></div>
        <div class="data-row"><dt>PESEL</dt><dd class="font-mono tracking-wide">{{ $user->pesel }}</dd></div>
        <div class="data-row"><dt>Data urodzenia</dt><dd>{{ $user->birthDate()?->format('d.m.Y') }}</dd></div>
        <div class="data-row"><dt>Płeć</dt><dd>{{ $user->genderLabel() }}</dd></div>
        <div class="data-row"><dt>Rola</dt><dd>{{ $user->roleLabel() }}</dd></div>
        <div class="data-row"><dt>Numer konta</dt><dd class="font-mono tracking-wide">{{ $user->formattedAccountNumber() }}</dd></div>
        <div class="data-row"><dt>Saldo</dt><dd class="font-semibold tabular-nums">{{ number_format($user->balance, 2, ',', ' ') }} zł</dd></div>
    </dl>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Edytuj</a>
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Usunąć użytkownika?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger-outline w-full sm:w-auto">Usuń użytkownika</button>
        </form>
    </div>
@endsection
