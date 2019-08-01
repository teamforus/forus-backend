@extends('emails.base')

@section('button_text', 'Ga naar het dashboard')
@section('link', $sponsor_dashboard_link)
@section('title', $provider_name . ' wilt zich aanmelden voor '. $fund_name)
@section('header_image', 'https://media.forus.io/static/bla.png')

@section('html')
    Beste {{ $sponsor_name }},
    <br/>
    <br/>
    Er is een aanmelding binnengekomen om deel te nemen aan {{ $fund_name }}.
    <br/>
    <br/>
    Controleer of {{ $provider_name }} voldoet aan uw voorwaarden om deel te nemen.
    <br/>
    Meld u aan op het sponsor dashboard <a href="{{ $sponsor_dashboard_link }}" target="_blank" style="color: #315efd; text-decoration: underline;">{{ $sponsor_dashboard_link }}</a> om deze aanvraag te behandelen.
@endsection
