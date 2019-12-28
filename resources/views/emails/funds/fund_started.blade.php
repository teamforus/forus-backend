@extends('emails.base')

@section('title', mail_trans('fund_started.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('fund_started.dear_provider') }},
    <br/>
    <br/>
    {{ mail_trans('fund_started.approved', ['fund_name' => $fund_name, 'sponsor_name' => $sponsor_name]) }}
    <br/>
    {{ mail_trans('fund_started.active', ['fund_name' => $fund_name]) }}
    <br/>
@endsection