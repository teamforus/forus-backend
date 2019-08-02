@extends('emails.base)
@section('title', 'Uw fonds heeft uw ingestelde grens bereikt')
@section('link', $link)
@section('html')
    Beste {{ $sponsor_name }},
    <br />
    <br />
    Het budget op fonds '{{ $fund_name }}' heeft de grens van €{{ $notification_amount }} bereikt.
    <br />
    U kunt inloggen op het sponsordashboard om uw budget aan te vullen.
@endsection
