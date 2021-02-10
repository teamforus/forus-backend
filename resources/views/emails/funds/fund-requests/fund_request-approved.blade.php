<?php
    /** @var string $request_status */
    /** @var string $fund_name */
    /** @var string $webshop_link Link to webshop */
?>
@extends('emails.base')
@section('button_text', mail_trans('fund_request_approved.webshop_button'))
@section('link', $webshop_link)
@section('title', mail_trans('fund_request_approved.title', ['fund_name' => $fund_name]))

@section('html')
    {{ mail_trans('dear_citizen') }},
    <br/>
    <br/>
    {!! mail_trans('fund_request_approved.message', ['fund_name' => $fund_name, 'webshop_link' => $webshop_link]) !!}
    <br/>
    <br/>
    {!! mail_trans('fund_request_approved.download_app', ['app_link' => $app_link]) !!}
    <br/>
    <br/>
@endsection
