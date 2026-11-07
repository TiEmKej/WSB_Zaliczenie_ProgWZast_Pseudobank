@extends('layouts.app')

@section('title', 'Nowy użytkownik')
@section('width', 'max-w-xl')

@section('content')
    <a href="{{ route('admin.users.index') }}" class="link-row mb-2">← Wróć do listy</a>
    <h1 class="page-title mb-6">Nowy użytkownik</h1>
    <form method="POST" action="{{ route('admin.users.store') }}" class="card grid gap-5 sm:grid-cols-2">
        @include('admin.users._form', ['user' => new App\Models\User])
    </form>
@endsection
