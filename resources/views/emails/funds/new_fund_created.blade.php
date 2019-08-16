@extends('emails.base')

@section('title', implementation_trans('new_fund_created.title'))
@section('html')
    {{ implementation_trans('dear_citizen') }}
    <br/>
    <br/>
    {{ implementation_trans('new_fund_created.new_fund_created') }}
    <br/>
    <br/>
    {!! implementation_trans('new_fund_created.webshop_button', ['link' => 'webshop_link', 'fund_name' => $fund_name]) !!}
@endsection
