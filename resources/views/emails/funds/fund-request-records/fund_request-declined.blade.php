<?php
    /** @var string $fund_name */
    /** @var string $webshop_link Link to webshop */
    /** @var string rejection_note Reason for rejection */
    $viewData = compact('fund_name', 'webshop_link', 'rejection_note');
?>
@extends('emails.base')

@section('title', mail_trans('fund_request_record_declined.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_citizen') }}
    <br/>
    {{ mail_trans('fund_request_record_declined.message', ['fund_name' => $fund_name]) }}
    <br/>
    {!! mail_trans('fund_request_record_declined.reason', ['reason' => $rejection_note]) !!}
    <br/>
    <br/>
    {!! mail_trans('fund_request_record_declined.webshop_button', ['link' => $webshop_link]) !!}
@endsection
