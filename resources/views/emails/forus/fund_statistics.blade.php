@extends('emails.base')

@section('title', implementation_trans('fund_statistics.title', [
    'sponsor_name' => $sponsor_name,
    'fund_name' => $fund_name
]))
@section('html')
    {{ implementation_trans('fund_statistics.sponsor', ['sponsor_name' => $sponsor_name]) }}
    <br />
    <br />
    {{ implementation_trans('fund_statistics.fund', ['fund_name' => $fund_name]) }}
    <br />
    <br />
    {{ implementation_trans('fund_statistics.sponsor_count', ['sponsor_amount' => $sponsor_amount]) }}
    <br />
    {{ implementation_trans('fund_statistics.provider_count', ['provider_count' => $provider_amount]) }}
    <br />
    {{ implementation_trans('fund_statistics.request_amount', ['request_amount' => $request_amount]) }}
    <br />
    <hr>
    {{ implementation_trans('fund_statistics.total_amount', ['total_amount' => $total_amount]) }}
    <br />
@endsection

