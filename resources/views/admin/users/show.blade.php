@extends('layouts.app')

@section('title', $user->name)

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">{{ $user->name }}</h1>

    <dl class="space-y-2 rounded bg-white p-6 shadow">
        <div><dt class="text-sm text-slate-500">E-mail</dt><dd>{{ $user->email }}</dd></div>
        <div><dt class="text-sm text-slate-500">Rola</dt><dd>{{ $user->roleLabel() }}</dd></div>
        <div><dt class="text-sm text-slate-500">Numer konta</dt><dd class="font-mono">{{ $user->account_number }}</dd></div>
        <div><dt class="text-sm text-slate-500">Saldo</dt><dd>{{ number_format($user->balance, 2, ',', ' ') }} zł</dd></div>
    </dl>

    <div class="mt-4 flex gap-4">
        <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-600 hover:underline">Edytuj</a>
        <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:underline">Wróć do listy</a>
    </div>
@endsection
