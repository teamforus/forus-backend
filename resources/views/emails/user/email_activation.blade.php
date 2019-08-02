@extends('emails.base')

@section('button_text', 'Ga naar stap 3')
@section('link', $link)
@section('title', 'Stap 2 van 3: E-mailadres bevestigen')
@section('header_image', 'https://media.forus.io/static/iphone_shield_594x594.png')
@section('html')
U krijgt deze e-mail omdat u op een gemeentelijke webshop uw e-mailadres hebt ingevuld. Met deze e-mail willen we bevestigen of u toegang heeft tot dit e-mailadres.
<br/>
<br/>
Als u op deze <a style="color: #315efd; text-decoration: underline;" href="{{ $link }}" target="blank">link</a> klikt of op de onderstaande knop wordt uw e-mailadres bevestigd.
<br/>
@endsection
