@extends('emails.base')
@section('title', 'Uw aanbod' . $product_name . 'is gereserveerd!')
@section('html')
Beste gebruiker,<br/>
<br/>
<br/>
Zojuist heeft iemand uw aanbieding '{{ $product_name }}' in de webshop gereserveerd. De klant kan daarom elk moment deze aanbieding komen ophalen of afnemen.<br/>
<br/>
De uiterlijke datum dat de klant langs kan komen is {{ $expiration_date }}<br/>
<br/>
Hopelijk hebben we u hiermee voldoende geïnformeerd.<br/>
@endsection
