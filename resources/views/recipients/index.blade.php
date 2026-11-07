@extends('layouts.app')

@section('title', 'Książka odbiorców')
@section('width', 'max-w-3xl')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Książka odbiorców</h1>

    <table class="mb-8 w-full rounded bg-white shadow">
        <thead>
            <tr class="border-b text-left">
                <th class="p-3">Nazwa i adres</th>
                <th class="p-3">Numer konta</th>
                <th class="p-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recipients as $recipient)
                <tr class="border-b">
                    <td class="p-3">{{ $recipient->name }}<br><span class="text-sm text-slate-500">{{ $recipient->address }}</span></td>
                    <td class="p-3 font-mono">{{ $recipient->formattedAccountNumber() }}</td>
                    <td class="flex justify-end gap-3 p-3">
                        <a href="{{ route('transfers.create', ['recipient' => $recipient->id]) }}" class="text-blue-600 hover:underline">Zrób przelew</a>
                        <form method="POST" action="{{ route('recipients.destroy', $recipient) }}" onsubmit="return confirm('Usunąć odbiorcę z książki?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600 hover:underline">Usuń</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="p-3 text-center text-slate-500">Książka odbiorców jest pusta.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="mb-2 text-xl font-semibold">Dodaj odbiorcę</h2>
    <form method="POST" action="{{ route('recipients.store') }}" class="max-w-md space-y-4">
        @csrf
        <div>
            <label for="name" class="block">Nazwa odbiorcy</label>
            <input id="name" name="name" value="{{ old('name') }}" class="w-full rounded border p-2">
        </div>
        <div>
            <label for="address" class="block">Adres odbiorcy</label>
            <input id="address" name="address" value="{{ old('address') }}" class="w-full rounded border p-2">
        </div>
        <div>
            <label for="account_number" class="block">Numer konta</label>
            <input id="account_number" name="account_number" value="{{ App\Support\AccountNumber::format(old('account_number')) }}" class="w-full rounded border p-2 font-mono">
        </div>
        <button class="w-full rounded bg-blue-600 p-2 text-white hover:bg-blue-700">Zapisz odbiorcę</button>
    </form>
@endsection
