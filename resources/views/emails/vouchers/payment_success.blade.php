<?php
/** @var bool $informalCommunication */
/** @var string $communicationType */
/** @var \App\Services\Forus\Notification\EmailFrom $emailFrom */
?>

@extends('emails.base')

@section('title', mail_trans("payment_success.title_$communicationType", $data))
@section('html')
    {{ mail_trans('dear_user') }}
    <br/>
    <br/>
    {{ mail_trans('payment_success.something_bought_something_withdrawn') }}
    <br/>
    {{ mail_trans('payment_success.current_value', $data) }}
@endsection
