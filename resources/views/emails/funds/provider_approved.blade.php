@extends('emails.base')

@section('title', implementation_trans('provider_approved.title'))
@section('html')
    {{ implementation_trans('provider_approved.application_approved') }}
    <br/>
    <br/>
    {{ implementation_trans('dear_provider', ['provider_name' => $provider_name]) }}
    <br/>
    <br/>
    {{ implementation_trans('provider_approved.applied_for', ['fund_name' => $fund_name]) }}
    <br/>
    {{ implementation_trans('provider_approved.sponsor_application_approved', ['sponsor_name' => $sponsor_name]) }}
    <br/>
    <br/>
    {{ implementation_trans('provider_approved.this_means_that', ['fund_name' => $fund_name]) }}
    <br/>
    <br/>
    {{ implementation_trans('greets') }},
    <br/>
    <br/>
    {{ implementation_trans('team_fund', ['fund_name' => $fund_name]) }}
    <br />
    <br/>
    <br/>
    {{ implementation_trans('provider_approved.log_in', ['link' => $provider_dashboard_link]) }}
@endsection

