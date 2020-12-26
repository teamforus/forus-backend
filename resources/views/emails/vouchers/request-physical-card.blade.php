@extends('emails.base')

@section('title', mail_trans('request_physical_card.title'))
@section('html')
    {{ mail_trans('dear_citizen') }},
    <br/>
    <br/>
    {!! mail_trans('request_physical_card.description', $data) !!}
    <br/>
    <br/>
    <strong>{{ $data['street_name'] }} {{ $data['house_number'] }} {{ $data['house_addition'] }}</strong>
    <br/>
    <strong>{{ $data['postcode'] }} {{ $data['city'] }}</strong>
    <br/>
    <br/>
    {{ mail_trans('request_physical_card.contact_us', $data) }}
    <br/>
    <br/>
    {{ mail_trans('request_physical_card.greets') }}
    <br/>
@endsection