@extends('layouts.app')

@section('title', 'Nowy użytkownik')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Nowy użytkownik</h1>
    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
        @include('admin.users._form', ['user' => new App\Models\User])
    </form>
@endsection
