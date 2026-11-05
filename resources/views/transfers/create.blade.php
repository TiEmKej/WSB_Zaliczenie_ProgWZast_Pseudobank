@extends('layouts.app')

@section('title', 'Nowy przelew')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Nowy przelew</h1>
    <form method="POST" action="{{ route('transfers.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="account_number" class="block">Numer konta odbiorcy</label>
            <input id="account_number" name="account_number" value="{{ old('account_number') }}" class="w-full rounded border p-2 font-mono">
        </div>
        <div>
            <label for="amount" class="block">Kwota (zł)</label>
            <input id="amount" name="amount" value="{{ old('amount') }}" class="w-full rounded border p-2">
        </div>
        <div>
            <label for="title" class="block">Tytuł</label>
            <input id="title" name="title" value="{{ old('title') }}" class="w-full rounded border p-2">
        </div>
        <button class="w-full rounded bg-blue-600 p-2 text-white hover:bg-blue-700">Dalej</button>
    </form>
@endsection
