@extends('pages.layout')

@section('content')
    <h2 class="title text-center">Your email '{{ $email }}' have been successfully unsubscribed from all emails.</h2>
    <p>
        <a class="button text-center" href="{{ $reSubLink }}" onclick="onClick()">Cancel</a>
    </p>
@endsection