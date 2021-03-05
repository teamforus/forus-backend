<?php
    /** @var string $request_status */
    /** @var string $fund_name */
?>
@extends('emails.base')
@if ($emailFrom->isInformalCommunication())
    @section('title', mail_trans('fund_request_resolved.title_informal', ['fund_name' => $fund_name])) 
@else
    @section('title', mail_trans('fund_request_resolved.title_formal', ['fund_name' => $fund_name]))   
@endif
@section('html')
    {{ mail_trans('dear_citizen') }},
    <br/>
    <br/>

    @if ($clarification_msg)
        {{ $clarification_msg }}
        <br/>
        <br/>
    @endif

    @if ($emailFrom->isInformalCommunication())
        {!! mail_trans('fund_request_resolved.contact_us_informal', ['fund_name' => $fund_name]) !!} 
    @else
        {!! mail_trans('fund_request_resolved.contact_us_formal', ['fund_name' => $fund_name]) !!}
    @endif
    <br/>
    <br/>
    {{ $sponsor_name }}
    <br/>
    {{ mail_trans('fund_request_resolved.sponsor_phone', ['sponsor_phone' => $sponsor_phone]) }}
    <br/>
    {{ mail_trans('fund_request_resolved.sponsor_email', ['sponsor_email' => $sponsor_email]) }}
@endsection
