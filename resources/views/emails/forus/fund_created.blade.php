<?php
/** @var string $communicationType */
/** @var string $subject */
/** @var array $data */
?>

@extends('emails.base')
@section('title', $subject)

@section('html')
    {!! trans('mails/_misc.dear_forus', $data) !!},
    <br/>
    <br/>
    {!! trans('mails/fund_created.new_fund_created', $data) !!}
    <br/>
    {!! trans('mails/fund_created.by', $data) !!}
    <br/>
    <br/>
@endsection
