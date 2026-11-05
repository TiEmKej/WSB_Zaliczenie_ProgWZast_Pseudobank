@extends('layouts.app')

@section('title', 'Edycja użytkownika')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Edycja użytkownika</h1>
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
        @method('PUT')
        @include('admin.users._form')
    </form>
@endsection
