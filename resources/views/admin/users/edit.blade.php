@extends('layouts.app')

@section('title', 'Edycja użytkownika')
@section('width', 'max-w-xl')

@section('content')
    <a href="{{ route('admin.users.index') }}" class="link-row mb-2">← Wróć do listy</a>
    <h1 class="page-title mb-6">Edycja użytkownika</h1>
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="card grid gap-5 sm:grid-cols-2">
        @method('PUT')
        @include('admin.users._form')
    </form>
@endsection
