@extends('pages.layout')

@section('styles')
    <link rel="stylesheet" href="{{ asset('/assets/css/notification-styles.css?time=' . time()) }}">
@endsection

@section('content')
    <br>
    <br>
    <br>
    <h2 class="title text-center">From now on you will continue to receive emails.</h2>
    <p class="text-center">
        <a class="button text-center" href="{{ $unSubLink }}" onclick="onClick()">Unsubscribe from all emails</a>
    </p>
@endsection