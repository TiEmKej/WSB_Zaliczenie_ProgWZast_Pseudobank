@extends('layouts.app')

@section('title', 'Użytkownicy')
@section('width', 'max-w-5xl')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="page-title">Użytkownicy</h1>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Dodaj użytkownika</a>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 flex gap-2">
        <label for="q" class="sr-only">Szukaj po adresie e-mail</label>
        <input id="q" type="search" name="q" value="{{ $search }}" placeholder="Szukaj po adresie e-mail" class="field h-12 flex-1">
        <button class="btn btn-primary h-12">Szukaj</button>
    </form>

    @if ($users->isEmpty())
        <div class="card text-center">
            <p class="font-semibold">Brak użytkowników.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="hidden w-full sm:table">
                <thead>
                    <tr>
                        <th class="th">Imię i nazwisko</th>
                        <th class="th">E-mail</th>
                        <th class="th">Rola</th>
                        <th class="th text-right">Saldo</th>
                        <th class="th"><span class="sr-only">Akcje</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td class="td font-semibold">{{ $user->name }}</td>
                            <td class="td text-stone-600">{{ $user->email }}</td>
                            <td class="td"><x-role-badge :user="$user" /></td>
                            <td class="td whitespace-nowrap text-right tabular-nums">{{ number_format($user->balance, 2, ',', ' ') }} zł</td>
                            <td class="td">
                                <div class="flex items-center justify-end gap-4">
                                    <a href="{{ route('admin.users.show', $user) }}" class="link-row">Podgląd</a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="link-row">Edytuj</a>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Usunąć użytkownika?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="link-danger">Usuń</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <ul class="sm:hidden">
                @foreach ($users as $user)
                    <li class="mobile-row gap-1">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-semibold">{{ $user->name }}</span>
                            <x-role-badge :user="$user" />
                        </div>
                        <span class="break-all text-sm text-stone-600">{{ $user->email }}</span>
                        <span class="tabular-nums">{{ number_format($user->balance, 2, ',', ' ') }} zł</span>
                        <div class="mt-2 flex items-center gap-3">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary flex-1">Podgląd</a>
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary flex-1">Edytuj</a>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Usunąć użytkownika?')">
                                @csrf
                                @method('DELETE')
                                <button class="link-danger px-3">Usuń</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6">{{ $users->links() }}</div>
@endsection
