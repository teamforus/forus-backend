@extends('pages.layout')

@section('styles')
    <link rel="stylesheet" href="{{ asset('/assets/css/notification-styles.css?time=' . time()) }}">
@endsection

@section('content')
    <br>
    <br>
    <br>
    <h2 class="title text-center">Your email '{{ $email }}' have been successfully unsubscribed from all emails.</h2>
    <p class="text-center">
        <a class="button button-primary text-center" href="{{ $reSubLink }}" onclick="onClick()">Cancel</a>
    </p>
@endsection