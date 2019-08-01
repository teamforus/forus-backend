@extends('emails.base)

@section('title', trans('mails.funds.provider_approved.title'))
@section('html')
    Uw aanmelding is geaccepteerd!
    <br/>
    <br/>
    Beste {{ $provider_name }},
    <br/>
    <br/>
    Kort geleden heeft u zich aangemeld voor '{{ $fund_name }}'.
    <br/>
    {{ $sponsor_name }} heeft uw aanmelding geaccepteerd.
    <br/>
    <br/>
    Dit betekent dat u vanaf nu uw aanbiedingen kan leveren aan klanten die recht hebben op {{ $fund_name }}.
    <br/>
    <br/>
    Met vriendelijke groet,
    <br/>
    <br/>
    Team {{$fund_name}}<br />
    <br/>
    <br/>
    Log in op uw gebruikersomgeving om aanbiedingen toe te voegen, door <a href="{{ $provider_dashboard_link }}" target="_blank" style="color: #315efd; text-decoration: underline;">hier</a> of op de onderstaande knop te klikken.<br/>
@endsection

