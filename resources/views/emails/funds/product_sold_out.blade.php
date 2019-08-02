@extends('emails.base')
@section('title', 'Uitverkocht: aanbod ' . $product_name)
@section('button_text', 'Inloggen')
@section('link', $link)
@section('html')
Beste gebruiker,<br />
<br />
Uw totale aanbod in de webshop voor '{{ $product_name }}' is uitverkocht.<br />
U kunt nu het aanbod aanvullen of verwijderen. Als u het aanbod verwijderd kunt u daarna een nieuw aanbod plaatsen.<br />
<br />
Dit kunt u doen door in te loggen op het dashboard. Klik <a href="{{ $link }}" target="_blank" style="color: #315efd; text-decoration: underline;">hier</a> of op de knop hieronder om naar het dashboard te gaan. <br/>
@endsection
