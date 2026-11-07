@extends('layouts.app')

@section('title', 'Książka odbiorców')
@section('width', 'max-w-3xl')

@section('content')
    <h1 class="page-title mb-6">Książka odbiorców</h1>

    @if ($recipients->isEmpty())
        <div class="card mb-10 text-center">
            <p class="font-semibold">Książka odbiorców jest pusta.</p>
        </div>
    @else
        <div class="table-wrap mb-10">
            <table class="hidden w-full sm:table">
                <thead>
                    <tr>
                        <th class="th">Nazwa i adres</th>
                        <th class="th">Numer konta</th>
                        <th class="th"><span class="sr-only">Akcje</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recipients as $recipient)
                        <tr>
                            <td class="td"><span class="font-semibold">{{ $recipient->name }}</span><br><span class="text-sm text-stone-600">{{ $recipient->address }}</span></td>
                            <td class="td font-mono tracking-wide">{{ $recipient->formattedAccountNumber() }}</td>
                            <td class="td">
                                <div class="flex items-center justify-end gap-4">
                                    <a href="{{ route('transfers.create', ['recipient' => $recipient->id]) }}" class="link-row">Zrób przelew</a>
                                    <form method="POST" action="{{ route('recipients.destroy', $recipient) }}" onsubmit="return confirm('Usunąć odbiorcę z książki?')">
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
                @foreach ($recipients as $recipient)
                    <li class="mobile-row gap-1">
                        <span class="font-semibold">{{ $recipient->name }}</span>
                        <span class="text-sm text-stone-600">{{ $recipient->address }}</span>
                        <span class="font-mono text-sm tracking-wide">{{ $recipient->formattedAccountNumber() }}</span>
                        <div class="mt-2 flex items-center gap-3">
                            <a href="{{ route('transfers.create', ['recipient' => $recipient->id]) }}" class="btn btn-secondary flex-1">Zrób przelew</a>
                            <form method="POST" action="{{ route('recipients.destroy', $recipient) }}" onsubmit="return confirm('Usunąć odbiorcę z książki?')">
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

    <h2 class="mb-3 text-xl font-semibold">Dodaj odbiorcę</h2>
    <form method="POST" action="{{ route('recipients.store') }}" class="card grid gap-5 sm:grid-cols-2">
        @csrf
        <x-field name="name" label="Nazwa odbiorcy" :value="old('name')" />
        <x-field name="address" label="Adres odbiorcy" :value="old('address')" />
        <div class="sm:col-span-2">
            <x-field name="account_number" label="Numer konta" mono :value="App\Support\AccountNumber::format(old('account_number'))" />
        </div>
        <button class="btn btn-primary btn-block sm:col-span-2 sm:w-auto sm:self-start">Zapisz odbiorcę</button>
    </form>
@endsection
