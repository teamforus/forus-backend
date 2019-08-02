@extends('emails.base)
@section('title', implementation_trans('fund_expires.title'))
@section('html')
{{ implementation_trans('dear_user_of_fund', ['fund_name' => $fund_name])}}
<br/>
<br/>
    {{ implementation_trans('fund_expires.on_date_have_recieved', [
        'start_date_fund' => $start_date_fund,
        'sponsor_name' => $sponsor_name,
        'fund_name' => $fund_name
    ]) }}
    <br/>
    {{ implementation_trans('fund_expires.voucher_due_to', [
        'fund_name' => $fund_name,
        'end_date_fund' => $end_date_fund
    ]) }}
    <br/>
    {{ implementation_trans('fund_expires.see_budget_and_transactions', ['link' => $shop_implementation_url]) }}
    <br/>
    {{ implementation_trans('fund_expires.next_log_in') }}
    <br/>
    {{ implementation_trans('fund_expires.get_in_contact') }}
    <br/>
    {{ $phonenumber_sponsor }}
    <br/>
    {{ $emailaddress_sponsor }}
    <br/>
    {{ implementation_trans('hopefully_informed_enough') }}
    <br/>
    {{ implementation_trans('greets') }}
    {{ $sponsor_name }}
@endsection
