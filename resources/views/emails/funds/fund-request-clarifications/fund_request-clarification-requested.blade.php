<?php
/** @var string $fund_name */
/** @var string $webshop_link_clarification Link to clarification answer page */
/** @var string $question Question asked by validator */
/** @var bool $informalCommunication */
/** @var string $communicationType */
/** @var \App\Services\Forus\Notification\EmailFrom $emailFrom */
$viewData = compact('fund_name', 'question', 'webshop_link_clarification');
?>

@extends('emails.base')

@section('title', mail_trans("fund_request_clarification_requested.title_$communicationType", ['fund_name' => $fund_name]))
@section('button_text', mail_trans("fund_request_clarification_requested.button_text_$communicationType"))
@section('link', $webshop_link_clarification)

@section('html')
    {{ $question }}
    <br/>
    <br/>
@endsection
