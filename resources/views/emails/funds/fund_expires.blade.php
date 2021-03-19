@extends('emails.base')
@section('title', mail_trans('fund_expires.title', ['fund_name' => $fund_name]  ))
@section('html')
{{ mail_trans('dear_user_of_fund', ['fund_name' => $fund_name])}}<br/>
<br/>
    @if ($emailFrom->isInformalCommunication())
        {{ mail_trans('fund_expires.on_date_have_recieved_informal', [
            'fund_start_year' => $start_date_fund->format('Y'),
            'sponsor_name' => $sponsor_name,
            'fund_name' => $fund_name
        ]) }}
        <br/><br/>
        {{ mail_trans('fund_expires.voucher_due_to_informal', [
            'fund_name' => $fund_name,
            'fund_last_active_date' => format_date_locale($end_date_fund, 'long_date_locale'),
            'fund_expired_date' => format_date_locale($end_date_fund->clone()->addDay(), 'long_date_locale'),
        ]) }}
        <br/><br/>
        {!! mail_trans('fund_expires.see_budget_and_transactions_informal', ['link' => $shop_implementation_url]) !!}
        {{ mail_trans('fund_expires.next_log_in_informal') }} <br/>
    @else
        {{ mail_trans('fund_expires.on_date_have_recieved_formal', [
            'fund_start_year' => $start_date_fund->format('Y'),
            'sponsor_name' => $sponsor_name,
            'fund_name' => $fund_name
        ]) }}
        <br/><br/>
        {{ mail_trans('fund_expires.voucher_due_to_formal', [
            'fund_name' => $fund_name,
            'fund_last_active_date' => format_date_locale($end_date_fund, 'long_date_locale'),
            'fund_expired_date' => format_date_locale($end_date_fund->clone()->addDay(), 'long_date_locale'),
        ]) }}
        <br/><br/>
        {!! mail_trans('fund_expires.see_budget_and_transactions_formal', ['link' => $shop_implementation_url]) !!}
        {{ mail_trans('fund_expires.next_log_in_formal') }} <br/>
    @endif
    <br/>
    {{ mail_trans('fund_expires.get_in_contact') }}<br/>
    <br/>
    {{ $phone_number_sponsor }}<br/>
    <br/>
    {{ $email_address_sponsor }}<br/>
    <br/>
    {{ mail_trans('hopefully_informed_enough') }}<br/>
    <br/>
    {{ mail_trans('greets') }}<br />
    {{ mail_trans('team_fund', ['fund_name' => $fund_name]) }}
    {{ $sponsor_name }}
@endsection
