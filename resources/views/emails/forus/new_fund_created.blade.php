@extends('emails.base')
@section('title', mail_trans('forus/fund_created.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_forus') }}
    <br />
    <br />
    {{ mail_trans('forus/fund_created.new_fund_created', ['fund_name' => $fund_name]) }}
    <br />
    {{ mail_trans('forus/fund_created.by', ['organization_name' => $organization_name]) }}
@endsection
