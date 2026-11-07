@extends('layouts.app')

@section('title', 'Aktywacja konta')

@section('content')
    <div class="card">
        <span class="mb-4 flex size-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-900">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
        </span>
        <h1 class="page-title">Aktywuj konto</h1>
        <p class="mb-6 mt-3">Wysłaliśmy link aktywacyjny na adres <strong class="break-all">{{ auth()->user()->email }}</strong>. Kliknij go, aby korzystać z konta.</p>
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn btn-primary btn-block">Wyślij link ponownie</button>
        </form>
    </div>
@endsection
