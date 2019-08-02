@extends('emails.base')
@section('title', implementation_trans('fund_created.title'))
@section('html')
    {{ implementation_trans('dear_forus') }}
    <br />
    <br />
    {{ implementation_trans('fund_created.new_fund_created', ['fund_name' => $fund_name]) }}
    {{ implementation_trans('fund_created.by', ['organization_name' => $organiation_name]) }}
@endsection
