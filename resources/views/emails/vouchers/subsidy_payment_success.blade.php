@extends('emails.base')

@section('title', mail_trans('subsidy_payment_success.title', $data))
@section('html')
    {!! mail_trans('subsidy_payment_success.details', $data) !!}
    <br/>
    <br/>
    {!! mail_trans('subsidy_payment_success.webshop_button', $data) !!}
@endsection
