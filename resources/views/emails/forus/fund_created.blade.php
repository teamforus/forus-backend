@extends('emails.base')
@section('title', 'Er is een nieuw fonds toegevoegd: ' . $fund_name)
@section('html')
Beste Forus,
<br/>
<br/>
Er is een nieuw fonds aangemaakt: {{ $fund_name }}     <br/>
Door: {{ $organization_name }}    <br/>
<br/>
@endsection
