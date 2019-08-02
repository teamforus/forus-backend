@extends('emails.base)
@section('title', $fund_name . ' verloop binnenkort')
@section('html')
    Beste gebruik van {{ $fund_name }},
    <br/>
    <br/>
    Op {{ $start_date_fund }} heeft u van {{ $sponsor_name }} een {{ $fund_name }}-voucher ontvangen.
    <br/>
    Uw {{ $fund_name }}-voucher is geldig tot en met {{ $end_date_fund }}. Vanaf {{ $end_date_fund }} is het budget niet meer geldig.
    <br/>
    U kunt uw huidige budget en transacties inzien via: {{ $shop_implementation_url }}.
    <br/>
    Log vervolgenns in met uw e-mailadres.
    <br/>
    Lukt het niet om in te loggen? Neem dan contact op met:
    <br/>
    {{ $phonenumber_sponsor }}
    <br/>
    {{ $emailaddress_sponsor }}
    <br/>
    We hopen u hiermee voldoende te hebben geinformeerd.
    <br/>
    Met vriendelijke groet,
    {{ $sponsor_name }}
@endsection
