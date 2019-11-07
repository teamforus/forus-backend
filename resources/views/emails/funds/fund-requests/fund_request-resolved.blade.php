<?php
    /** @var string $request_status */
    /** @var string $fund_name */
    /** @var string $webshop_link Link to webshop */
?>
@extends('emails.base')

@section('title', mail_trans('fund_request_resolved.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_citizen') }}
    <br/>
    <br/>
    {{ mail_trans('fund_request_resolved.message', ['status' => $request_status]) }}
    <br/>
    <br/>
    {!! mail_trans('fund_request_resolved.webshop_button', ['link' => $webshop_link]) !!}
@endsection
