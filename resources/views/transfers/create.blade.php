@extends('layouts.app')

@section('title', 'Nowy przelew')
@section('width', 'max-w-2xl')

@section('content')
    <x-stepper :current="1" />

    <h1 class="page-title mb-6">Nowy przelew</h1>

    @include('transfers._source')

    @if ($recipients->isNotEmpty())
        <div class="card mb-4 !p-5">
            <p class="eyebrow mb-3">Z książki odbiorców</p>
            <ul class="flex flex-wrap gap-2">
                @foreach ($recipients as $recipient)
                    <li>
                        <a href="{{ route('transfers.create', ['recipient' => $recipient->id]) }}"
                           @class(['chip', 'chip-active' => $prefill?->id === $recipient->id])
                           @if ($prefill?->id === $recipient->id) aria-current="true" @endif>{{ $recipient->name }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('transfers.store') }}" class="card space-y-5">
        @csrf
        <x-field name="account_number" label="1. Numer konta odbiorcy" mono :value="App\Support\AccountNumber::format(old('account_number', $prefill?->account_number))" />

        <div class="space-y-3">
            <x-field name="recipient_name" label="2. Nazwa i adres odbiorcy" placeholder="Nazwa odbiorcy" :value="old('recipient_name', $prefill?->name)" />
            <x-field name="recipient_address" label="Adres odbiorcy" hideLabel placeholder="Adres: ulica numer, kod pocztowy miasto" :value="old('recipient_address', $prefill?->address)" />
        </div>

        <x-field name="amount" label="3. Kwota (zł)" suffix="zł" :value="old('amount')" inputmode="decimal" />
        <x-field name="title" label="4. Tytuł" :value="old('title')" />

        <label class="flex min-h-11 items-center gap-2.5">
            <input type="checkbox" name="save_recipient" value="1" class="size-5 accent-emerald-950" @checked(old('save_recipient'))>
            Zapisz odbiorcę w książce odbiorców
        </label>

        <button class="btn btn-primary btn-block">Dalej</button>
    </form>
@endsection
