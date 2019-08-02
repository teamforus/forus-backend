@extends('emails.base')
@section('title', 'Er is een nieuw aanbod toegevoegd aan de webshop')
@section('html')
{% block html %}
Beste {{ $sponsor_name }},
<br/>
<br/>
Er is een nieuw aanbod geplaatst op de webshop.
<br/>
<br/>
Bekijk de webshop om te controleren of het voldoet aan de voorwaarden om vanuit {{ $fund_name }} aangeboden te worden.
@endsection
