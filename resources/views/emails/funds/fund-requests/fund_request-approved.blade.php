<?php
    /** @var string $request_status */
    /** @var string $fund_name */
    /** @var string $webshop_link Link to webshop */
?>
@extends('emails.base')
@section('button_text', mail_trans('fund_request_approved.webshop_button'))
@section('link', $webshop_link)
@if ($emailFrom->isInformalCommunication())
    @section('title', mail_trans('fund_request_approved.title_informal', ['fund_name' => $fund_name]))
@else
    @section('title', mail_trans('fund_request_approved.title_formal', ['fund_name' => $fund_name]))   
@endif

@section('html')
    {{ mail_trans('dear_citizen') }},
    <br/>
    <br/>
    @if ($emailFrom->isInformalCommunication())
        {!! mail_trans('fund_request_approved.message_informal', ['fund_name' => $fund_name, 'webshop_link' => $webshop_link]) !!}
        <br/>
        <br/>
        {!! mail_trans('fund_request_approved.download_app_informal', ['fund_name' => $fund_name, 'app_link' => $app_link]) !!}
    @else
        {!! mail_trans('fund_request_approved.message_formal', ['fund_name' => $fund_name, 'webshop_link' => $webshop_link]) !!}
        <br/>
        <br/>
        {!! mail_trans('fund_request_approved.download_app_formal', ['fund_name' => $fund_name, 'app_link' => $app_link]) !!}  
    @endif
    <br/>
    <br/>
@endsection
