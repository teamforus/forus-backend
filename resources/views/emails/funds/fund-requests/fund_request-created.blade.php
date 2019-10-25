@extends('emails.base')

@section('title', mail_trans('fund_request_created.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_citizen') }}
    <br/>
    <br/>
    {{ mail_trans('fund_request_created.message', ['fund_name' => $fund_name]) }}
    <br/>
    <br/>
    {!! mail_trans('fund_request_created.webshop_button', ['link' => 'webshop_link') !!}
@endsection
