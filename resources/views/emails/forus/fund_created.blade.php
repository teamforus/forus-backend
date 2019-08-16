@extends('emails.base')
@section('title', implementation_trans('fund_created.title', ['fund_name' => $fund_name]))
@section('html')
    {{ implementation_trans('dear_forus') }},
    <br/>
    <br/>
    {{ implementation_trans('fund_created.new_fund_created', ['fund_name' => $fund_name]) }}
    <br/>
    {{ implementation_trans('fund_created.by', ['organization_name' => $organization_name]) }}
    <br/>
    <br/>
@endsection
