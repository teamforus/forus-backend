@extends('emails.base')

@section('button_text', 'Inloggen')
@section('link', $link)
@section('title', 'Log in op ' . $platform)
@section('header_image', 'https://media.forus.io/static/iphone_shield_594x594.png')

@section('html')
Beste gebruiker,
<br/>
<br/>
U heeft zojuist aangegeven dat u wilt inloggen op {{ $platform }}.
<br/>
Klik <a href="{{ $link }}" target="_blank" style="color: #315efd; text-decoration: underline;">hier</a> of op de knop hieronder om in te loggen.
<br/>
@endsection

