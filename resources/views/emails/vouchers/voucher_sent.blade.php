<?php
/** @var string $communicationType */
/** @var string $subject */
/** @var array $data */
?>

@extends('emails.base')
@section('title', $subject)

@section('html')
    {!! trans('mails/_misc.dear_user', $data) !!},
    <br/>
    <br/>
    @if ($emailFrom->isInformalCommunication())
        {!! trans('mails/voucher_sent.you_have_asked_informal', $data) !!}
        <br/>
        <br/>
        {!! trans('mails/voucher_sent.qr_code_under_informal', $data) !!}
        <br/>
        <img style="display: block; margin: 0 auto;" alt="" src="{{ $message->embedData(make_qr_code('voucher', $mailData['qr_token'] ?? ''), 'qr_token.png') }}" width="300" />
        <br/>
        {!! trans('mails/voucher_sent.provider_scans', $data) !!}
        <br/>
        <br/>
        {!! trans('mails/voucher_sent.have_fund_informal', $data) !!}
    @else
        {!! trans('mails/voucher_sent.you_have_asked_formal', $data) !!}
        <br/>
        <br/>
        {!! trans('mails/voucher_sent.qr_code_under_formal', $data) !!}
        <br/>
        <img style="display: block; margin: 0 auto;" alt="" src="{{ $message->embedData(make_qr_code('voucher', $qr_token), 'qr_token.png') }}" width="300" />
        <br/>
        {!! trans('mails/voucher_sent.provider_scans', $data) !!}
        <br/>
        <br/>
        {!! trans('mails/voucher_sent.have_fund_formal', $data) !!}
    @endif

@endsection
