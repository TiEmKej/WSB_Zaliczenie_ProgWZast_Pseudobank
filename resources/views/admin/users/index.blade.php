@extends('layouts.app')

@section('title', 'Użytkownicy')
@section('width', 'max-w-5xl')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Użytkownicy</h1>
        <a href="{{ route('admin.users.create') }}" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Dodaj użytkownika</a>
    </div>

    <table class="w-full rounded bg-white shadow">
        <thead>
            <tr class="border-b text-left">
                <th class="p-3">Imię i nazwisko</th>
                <th class="p-3">E-mail</th>
                <th class="p-3">Rola</th>
                <th class="p-3 text-right">Saldo</th>
                <th class="p-3"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr class="border-b">
                    <td class="p-3">{{ $user->name }}</td>
                    <td class="p-3">{{ $user->email }}</td>
                    <td class="p-3">{{ $user->roleLabel() }}</td>
                    <td class="p-3 text-right">{{ number_format($user->balance, 2, ',', ' ') }} zł</td>
                    <td class="flex justify-end gap-3 p-3">
                        <a href="{{ route('admin.users.show', $user) }}" class="text-blue-600 hover:underline">Podgląd</a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-600 hover:underline">Edytuj</a>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Usunąć użytkownika?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600 hover:underline">Usuń</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
