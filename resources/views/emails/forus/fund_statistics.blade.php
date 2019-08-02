@extends('emails.base')

@section('title', 'Totaal aantaal gebruikers ' . $sponsor_name. ' - ' . $fund_name)
@section('html')
Sponsor: {{ $sponsor_name }} <br />
<br />
Fonds: {{ $fund_name }} <br />
<br />
Sponsors accounts: {{ $sponsor_amount }}<br />
Aanbieders accounts: {{ $provider_amount }}<br />
Aanvragers accounts: {{ $requester_amount }}<br />
<hr>
Totaal aantal gebruikers: {{ $total_amount }}<br />
@endsection

