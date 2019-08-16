@extends('emails.base')
@section('title', implementation_trans('balance_warning.title'))
@section('link', $link)
@section('html')
    {{ implementation_trans('dear_sponsor', ['sponsor_name' => $sponsor_name]) }}
    <br />
    <br />
    {{ implementation_trans('balance_warning', ['fund_name' => $fund_name, 'notification_amount' => $notification_amount]) }}
    <br />
    {{ implementation_trans('balance_warning.you_can_login') }}
@endsection
