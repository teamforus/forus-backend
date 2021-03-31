<?php
    /** @var string $request_status */
    /** @var string $sponsor_name */
    /** @var string $webshop_link Link to webshop */
    /** @var bool $informalCommunication */
    /** @var string $communicationType */
    /** @var \App\Services\Forus\Notification\EmailFrom $emailFrom */
?>
@extends('emails.base')

@section('button_text', mail_trans('fund_request_created.webshop_button'))
@section('link', $webshop_link)

@section('title', mail_trans("fund_request_created.title_$communicationType", ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_citizen') }},
    <br/>
    <br/>
    {{ mail_trans("fund_request_created.message_$communicationType", ['fund_name' => $fund_name, 'sponsor_name' => $sponsor_name]) }}
    <br/>
    <br/>
@endsection
