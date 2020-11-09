<?php
    /** @var string $request_status */
    /** @var string $fund_name */
?>
@extends('emails.base')

@section('title', mail_trans('fund_request_resolved.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_citizen') }}
    <br/>
    <br/>

    @if ($clarification_msg)
        {{ $clarification_msg }}
        <br/>
        <br/>
    @endif

    {!! mail_trans('fund_request_resolved.contact_us', ['fund_name' => $fund_name]) !!}
    <br/>
    <br/>
    {{ $sponsor_name }}
    <br/>
    {{ mail_trans('fund_request_resolved.sponsor_phone', ['sponsor_phone' => $sponsor_phone]) }}
    <br/>
    {{ mail_trans('fund_request_resolved.sponsor_email', ['sponsor_email' => $sponsor_email]) }}
@endsection
