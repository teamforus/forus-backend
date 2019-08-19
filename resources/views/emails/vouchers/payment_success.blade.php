@extends('emails.base')

@section('title', mail_trans('payment_success.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_user') }}
    <br/>
    <br/>
    {{ mail_trans('payment_success.something_bought_something_withdrawn') }}
    <br/>
    {{ mail_trans('payment_success.current_value', ['fund_name' => $fund_name, 'current_budget' => $current_budget]) }}
@endsection
