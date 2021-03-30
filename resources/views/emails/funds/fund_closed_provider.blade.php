@extends('emails.base')

@section('title', mail_trans('fund_closed_provider.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_provider', ['provider_name' => $provider_name]) }}
    <br />
    <br />
    {{ mail_trans('fund_closed_provider.description', [
            'fund_name' => $fund_name,
            'end_date'  => $fund_end_date,
            'start_date'  => $fund_start_date,
        ])
    }} <br />
    <br/>
    {!! mail_trans('fund_closed_provider.dashboard_link', ['link' => $dashboard_link]) !!}
@endsection

