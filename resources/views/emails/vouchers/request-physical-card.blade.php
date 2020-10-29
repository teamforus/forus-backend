@extends('emails.base')

@section('title', mail_trans('request_physical_card.title'))
@section('html')
    {{ mail_trans('dear_citizen') }},
    <br/>
    <br/>
    {!! mail_trans('request_physical_card.description', ['fund_name' => $fund_name]) !!}
    <br/>
    <br/>
    <strong>{{ $street_name }} {{ $house_number }}</strong>
    <br/>
    <strong>{{ $postcode }} {{ $city }}</strong>
    <br/>
    <br/>
    {{ mail_trans('request_physical_card.contact_us', ['sponsor_email' => $sponsor_email, 'sponsor_phone' => $sponsor_phone]) }}
    <br/>
    <br/>
    {{ mail_trans('request_physical_card.greets') }}
    <br/>
@endsection