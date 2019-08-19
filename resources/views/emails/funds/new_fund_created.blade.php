@extends('emails.base')

@section('title', mail_trans('new_fund_created.title'))
@section('html')
    {{ mail_trans('dear_citizen') }}
    <br/>
    <br/>
    {{ mail_trans('new_fund_created.new_fund_created') }}
    <br/>
    <br/>
    {!! mail_trans('new_fund_created.webshop_button', ['link' => 'webshop_link', 'fund_name' => $fund_name]) !!}
@endsection
