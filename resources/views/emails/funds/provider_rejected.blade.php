@extends('emails.base')

@section('title', implementation_trans('provider_rejected.title', ['provider_rejected' => ]))
@section('html')
    {{ implementation_trans('dear_provider', ['provider_name' => $provider_name]) }},
    <br/>
    <br/>
    {{ implementation_trans('provider_rejected.applied_for_fund', ['fund_name' => $fund_name]) }}
    <br/>
    {{ implementation_trans('provider_rejected.sponsor_decided', [
        'sponsor_name' => $sponsor_name,
        'fund_name' => $fund_name
    ]) }}
    <br/>
    <br/>
    {{ implementation_trans('provider_rejected.want_to_know_more', ['sponsor_name' => $sponsor_name]) }}
    <br />
    {{ implementation_trans('provider_rejected.phone_number', ['phone_number' => $phone_number]) }}
    <br />
    {{ implementation_trans('hopefully_informed_enough') }}
    <br/>
    <br/>
    {{ implementation_trans('greets') }}
    <br/>
    <br/>
    {{ implementation_trans('team_fund', ['fund_name' => $fund_name]) }}
@endsection

