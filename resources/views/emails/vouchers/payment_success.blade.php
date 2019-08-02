@extends('emails.base')

@section('title', 'Er is een bedrag van uw ' . $fund_name .'-voucher afgeschreven')
@section('html')
Beste gebruiker,
<br/>
<br/>
Er is met uw voucher een aankoop gedaan. Hierdoor is er een bedrag afgeschreven.<br/>
Het huidige bedrag van uw '{{ $fund_name }}'-voucher is €{{ $current_budget }}
@endsection
