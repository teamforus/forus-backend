@extends('emails.base')

@section('title', mail_trans('voucher_assigned_budget.title', $data))
@section('html')
    {{ mail_trans('dear_user') }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned_budget.you_have_been_assigned', $data) }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned_budget.voucher_details', $data) }}
    <br/>
    <img style="display: block; margin: 0 auto;" alt="" src="{{ $message->embedData(make_qr_code('voucher', $data['qr_token']), 'qr_token.png') }}" width="300" />
    <br/>
    {{ mail_trans('voucher_assigned_budget.automatic_payment') }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned_budget.purchase_notice', $data) }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned_budget.have_fund') }}
    <br/>
@endsection