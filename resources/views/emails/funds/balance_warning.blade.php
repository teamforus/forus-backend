@extends('emails.base')
@section('title', mail_trans('balance_warning.title'))
@section('link', $link)
@section('html')
    {{ mail_trans('dear_sponsor', ['sponsor_name' => $sponsor_name]) }}
    <br />
    <br />
    {{ mail_trans('balance_warning', ['fund_name' => $fund_name, 'notification_amount' => $notification_amount]) }}
    <br />
    {{ mail_trans('balance_warning.you_can_login') }}
@endsection
