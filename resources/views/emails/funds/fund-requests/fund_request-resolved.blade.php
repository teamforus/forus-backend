<?php
    /** @var string $request_status */
    /** @var string $fund_name */
    /** @var string $webshop_link Link to webshop */
    $viewData = compact('fund_name', 'request_status', 'webshop_link');
?>
@extends('emails.base')

@section('title', mail_trans('fund_request_resolved.title', $viewData))
@section('html')
    {{ mail_trans('dear_citizen', $viewData) }}
    <br/>
    <br/>
    {{ mail_trans('fund_request_resolved.message', $viewData) }}
    {{ json_encode_pretty($viewData) }}
    <br/>
    <br/>
    {!! mail_trans('fund_request_resolved.webshop_button', $viewData) !!}
@endsection
