@extends('pages.layout')

@section('content')
    <br>
    <br>
    <br>
    <h2 class="title text-center">Your email '{{ $email }}' have been successfully unsubscribed from all emails.</h2>
    <p class="text-center">
        <a class="button button-sm button-primary text-center" href="{{ $reSubLink }}" onclick="onClick()">Cancel</a>
    </p>
@endsection