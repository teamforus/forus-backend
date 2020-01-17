@extends('emails.base')

@section('title', mail_trans('fund_closed_provider.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_provider', ['provider_name' => $provider_name]) }}
    <br />
    <br />
    {{ mail_trans('fund_closed_provider.description', [
            'fund_name' => $fund_name,
            'end_date'  => $fund_end_date,
            'end_date_minus1' => $fund_end_date->subDay()
        ])
    }} <br />
    <br/>
    {!! mail_trans('fund_closed_provider.dashboard_link', ['link' => $dashboard_link]) !!}
@endsection

