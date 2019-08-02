@extends('emails.base')
@section('title', 'Aanbieding QR-code gedeeld door ' . $requester_email)

@section('html')
{{ $product_name }}<br />
<img style="display: block; margin: 0 auto;" src="{{ $qr_url }}" width="300" />
<br />
{{ $requester_email }} heeft deze QR-code met u gedeeld met het volgende bericht:<br />
{{ $reason }}
@endsection
