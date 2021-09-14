<?php
/** @var string $communicationType */
/** @var string $subject */
/** @var array $data */
?>

@extends('emails.base')
@section('button_text', trans('mails/login_via_email.button_text'))
@section('link', str_replace('&amp;', '&', $data['link']))
@section('title', trans('mails/login_via_email.title', $data))
@section('header_image', mail_config('login_via_email.header_image', null, $data['implementationKey'] ?? null))

@section('html')
    {!! trans("mails/_misc.dear_user", $data) !!},
    <br/>
    <br/>
    {!! trans("mails/login_via_email.login_on_platform_" . $data['communicationType'], $data) !!}
    <br />
    {!! trans('mails/login_via_email.login_button', $data) !!}
    <br/>
    <br/>
    {!! trans('mails/login_via_email.login_expire', ['time' => strftime('%e %B %H:%M', strtotime("+1 hours"))]) !!}
@endsection

