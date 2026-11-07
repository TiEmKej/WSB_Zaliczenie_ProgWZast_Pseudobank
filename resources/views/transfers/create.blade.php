@extends('layouts.app')

@section('title', 'Nowy przelew')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Nowy przelew</h1>

    @if ($recipients->isNotEmpty())
        <div class="mb-4 rounded bg-white p-4 shadow">
            <p class="mb-2 text-sm text-slate-500">Z książki odbiorców</p>
            <ul class="flex flex-wrap gap-2">
                @foreach ($recipients as $recipient)
                    <li>
                        <a href="{{ route('transfers.create', ['recipient' => $recipient->id]) }}"
                           class="rounded border px-3 py-1 text-blue-600 hover:bg-blue-50">{{ $recipient->name }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('transfers.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="account_number" class="block">Numer konta odbiorcy</label>
            <input id="account_number" name="account_number" value="{{ App\Support\AccountNumber::format(old('account_number', $prefill)) }}" class="w-full rounded border p-2 font-mono">
        </div>
        <div>
            <label for="amount" class="block">Kwota (zł)</label>
            <input id="amount" name="amount" value="{{ old('amount') }}" class="w-full rounded border p-2">
        </div>
        <div>
            <label for="title" class="block">Tytuł</label>
            <input id="title" name="title" value="{{ old('title') }}" class="w-full rounded border p-2">
        </div>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="save_recipient" value="1" @checked(old('save_recipient'))>
            Zapisz odbiorcę w książce odbiorców
        </label>
        <button class="w-full rounded bg-blue-600 p-2 text-white hover:bg-blue-700">Dalej</button>
    </form>
@endsection
