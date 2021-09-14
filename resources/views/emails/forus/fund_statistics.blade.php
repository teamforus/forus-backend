<?php
/** @var string $communicationType */
/** @var string $subject */
/** @var array $data */
?>

@extends('emails.base')
@section('title', $subject)

@section('html')
    {!! trans('mails/fund_statistics.sponsor', $data) !!}
    <br />
    <br />
    {!! trans('mails/fund_statistics.fund', $data) !!}
    <br />
    <br />
    {!! trans('mails/fund_statistics.sponsor_count', $data) !!}
    <br />
    {!! trans('mails/fund_statistics.provider_count', $data) !!}
    <br />
    {!! trans('mails/fund_statistics.request_count', $data) !!}
    <br />
    <hr>
    {!! trans('mails/fund_statistics.total_amount', $data) !!}
    <br />
@endsection

