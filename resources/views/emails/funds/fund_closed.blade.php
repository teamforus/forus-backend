@extends('emails.base')

@section('title', mail_trans('fund_closed.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_citizen') }}
    <br />
    <br />
    {{ mail_trans('fund_closed.description', [
        'fund_name'    => $fund_name,
        'sponsor_name' => $sponsor_name,
        'end_date'     => $fund_end_date,
    ]) }} <br />
    {{ mail_trans('fund_closed.contact', [
        'fund_name'    => $fund_name,
        'fund_contact' => $fund_contact,
        'end_date'     => $fund_end_date,
    ]) }} <br />
    <br/>
    {!! mail_trans('fund_closed.webshop_link', ['link' => $webshop_link]) !!}
@endsection

