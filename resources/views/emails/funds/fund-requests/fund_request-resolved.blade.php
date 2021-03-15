<?php
    /** @var string $request_status */
    /** @var string $fund_name */
    /** @var string $webshop_link Link to webshop */
?>
@extends('emails.base')
@if ($emailFrom->isInformalCommunication())
    @section('title', mail_trans('fund_request_resolved.title_formal', ['fund_name' => $fund_name])) 
@else
    @section('title', mail_trans('fund_request_resolved.title_formal', ['fund_name' => $fund_name]))   
@endif
@section('html')
    {{ mail_trans('dear_citizen') }},
    <br/>
    <br/>
    @if ($emailFrom->isInformalCommunication())
        {{ mail_trans('fund_request_resolved.message_informal', ['status' => $request_status]) }}
    @else
        {{ mail_trans('fund_request_resolved.message_formal', ['status' => $request_status]) }} 
    @endif
    <br/>
    <br/>
    {!! mail_trans('fund_request_resolved.webshop_button', ['link' => $webshop_link]) !!}
@endsection
