@extends('pages.layout')

@section('styles')
    <style>
        .auth-error {
            max-width: 640px;
            margin: 12vh auto;
            padding: 24px;
            color: #222;
            font-family: Arial, sans-serif;
        }

        .auth-error h1 {
            margin: 0 0 16px;
            font-size: 28px;
            line-height: 1.3;
        }

        .auth-error p {
            margin: 0;
            font-size: 18px;
            line-height: 1.5;
        }
    </style>
@endsection

@section('content')
    <main class="auth-error">
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
    </main>
@endsection
