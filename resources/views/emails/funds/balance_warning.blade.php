@extends('emails.base')
@section('title', mail_trans('balance_warning.title', ['fund_name' => $fund_name]))
@section('link', $link)
@section('html')
    {{ mail_trans('dear_sponsor', ['sponsor_name' => $sponsor_name]) }}
    <br />
    <br />
    {{ mail_trans('balance_warning.budget_reached', ['fund_name' => $fund_name, 'notification_amount' => $notification_amount]) }}
    <br />
    {{ mail_trans('balance_warning.budget_left_fund', ['fund_name' => $fund_name, 'budget_left' => $budget_left]) }}
    <br />
    {{ mail_trans('balance_warning.no_transactions' }}
    <br/>
    {{ mail_trans('balance_warning.you_can_login') }}
@endsection
