@extends('emails.base)

@section('title', trans('mails.funds.new_fund_started.title'))
@section('html')
    Beste aanbieder,
    <br/>
    <br/>
    U bent onlangs goedgekeurd door {{$sponsor_name}} om deel te nemen aan {{$fund_name}}.
    <br/>
    Vanaf vandaag is dit fonds actief, dit betekent dat u vanaf vandaag klanten kan verwachten die gebruik willen maken van hun {{$fund_name}}-voucher.
    <br/>
@endsection