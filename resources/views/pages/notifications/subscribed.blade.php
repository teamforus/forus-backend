@extends('pages.layout')

@section('content')
    <h2 class="title text-center">From now on you will continue to receive emails.</h2>
    <p>
        <a class="button text-center" href="{{ $unSubLink }}" onclick="onClick()">Unsubscribe from all emails</a>
    </p>
@endsection