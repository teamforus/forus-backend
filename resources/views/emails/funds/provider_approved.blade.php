@extends('emails.base')

@section('title', mail_trans('provider_approved.title'))
@section('html')
    {{ mail_trans('provider_approved.application_approved') }}
    <br/>
    <br/>
    {{ mail_trans('dear_provider', ['provider_name' => $provider_name]) }}
    <br/>
    <br/>
    {{ mail_trans('provider_approved.applied_for', ['fund_name' => $fund_name]) }}
    <br/>
    {{ mail_trans('provider_approved.sponsor_application_approved', ['sponsor_name' => $sponsor_name]) }}
    <br/>
    <br/>
    {{ mail_trans('provider_approved.this_means_that', ['fund_name' => $fund_name]) }}
    <br/>
    <br/>
    {{ mail_trans('greets') }},
    <br/>
    <br/>
    {{ mail_trans('team_fund', ['fund_name' => $fund_name]) }}
    <br />
    <br/>
    <br/>
    {!! mail_trans('provider_approved.log_in', ['link' => $provider_dashboard_link])  !!}
@endsection

