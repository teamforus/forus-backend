@extends('emails.base')

@section('title', implementation_trans('payment_success.title', ['fund_name' => $fund_name]))
@section('html')
    {{ implementation_trans('dear_user') }}
    <br/>
    <br/>
    {{ implementation_trans('payment_success.something_bought_something_withdrawn') }}
    <br/>
    {{ implementation_trans('payment_success.current_value', ['fund_name' => $fund_name, 'current_budget' => $current_budget]) }}
@endsection
