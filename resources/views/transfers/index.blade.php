@extends('layouts.app')

@section('title', 'Historia przelewów')
@section('width', 'max-w-3xl')

@section('content')
    <h1 class="page-title mb-6">Historia przelewów</h1>

    @include('transfers._list', ['user' => auth()->user()])

    <div class="mt-6">{{ $transfers->links() }}</div>
@endsection
