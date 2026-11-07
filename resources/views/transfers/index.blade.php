@extends('layouts.app')

@section('title', 'Historia przelewów')
@section('width', 'max-w-3xl')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Historia przelewów</h1>

    @include('transfers._list', ['user' => auth()->user()])

    <div class="mt-4">{{ $transfers->links() }}</div>
@endsection
