<?php
/** @var string $communicationType */
/** @var string $subject */
/** @var array $data */
?>

@extends('emails.base')

@section('button_text', trans('mails/email_activation.button_text'))
@section('link', $link)
@section('title', $subject)
@section('header_image', mail_config('email_activation.header_image', null, $data['implementationKey'] ?? null))

@section('html')
    {!! trans("mails/email_activation.you_get_this_mail_because_" . $data['communicationType'], ['platform' => trans('mails/email_activation.platforms.' .  $clientType)]) !!}.
    <br/>
    <br/>
    {!! trans('mails/email_activation.confirmation_button_' . $data['communicationType'], $data) !!}.
@endsection
