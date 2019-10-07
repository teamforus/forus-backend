@extends('emails.base')
@section('title', mail_trans('fund_expires.title', ['fund_name' => $fund_name]  ))
@section('html')
{{ mail_trans('dear_user_of_fund', ['fund_name' => $fund_name])}}
<br/>
<br/>
    {{ mail_trans('fund_expires.on_date_have_recieved', [
        'start_date_fund' => $start_date_fund,
        'sponsor_name' => $sponsor_name,
        'fund_name' => $fund_name
    ]) }}
    <br/>
    {{ mail_trans('fund_expires.voucher_due_to', [
        'fund_name' => $fund_name,
        'end_date_fund' => $end_date_fund
    ]) }}
    <br/>
    {!! mail_trans('fund_expires.see_budget_and_transactions', ['link' => $shop_implementation_url]) !!}
    <br/>
    {{ mail_trans('fund_expires.next_log_in') }}
    <br/>
    {{ mail_trans('fund_expires.get_in_contact') }}
    <br/>
    {{ $phone_number_sponsor }}
    <br/>
    {{ $email_address_sponsor }}
    <br/>
    {{ mail_trans('hopefully_informed_enough') }}
    <br/>
    {{ mail_trans('greets') }}
    {{ $sponsor_name }}
@endsection
