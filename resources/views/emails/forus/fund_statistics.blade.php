@extends('emails.base')

@section('title', mail_trans('fund_statistics.title', [
    'fund_name' => $fund_name
]))
@section('html')
    <br />
    <br />
    {{ mail_trans('fund_statistics.fund', ['fund_name' => $fund_name]) }}
    <br />
    <br />
    {{ mail_trans('fund_statistics.sponsor_count', ['sponsor_count' => $sponsor_amount]) }}
    <br />
    {{ mail_trans('fund_statistics.provider_count', ['provider_count' => $provider_amount]) }}
    <br />
    {{ mail_trans('fund_statistics.request_count', ['request_count' => $request_amount]) }}
    <br />
    <hr>
    {{ mail_trans('fund_statistics.total_amount', ['total_count' => $total_amount]) }}
    <br />
@endsection

