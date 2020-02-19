@extends('emails.base')

@section('title', mail_trans('voucher_sent.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_user') }}
    <br/>
    <br/>
    {{ mail_trans('voucher_sent.you_have_asked', ['product_name' => $fund_product_name]) }}
    <br/>
    <br/>
    {{ mail_trans('voucher_sent.qr_code_under',[
        'voucher_amount'      => $voucher_amount,
        'expire_at_minus_day' => $voucher_expire_minus_day 
    ]) }}
    <br/>
    <img style="display: block; margin: 0 auto;" alt="" src="{{ $message->embedData(make_qr_code('voucher', $qr_token), 'qr_token.png') }}" width="300" />
    <br/>
    {{ mail_trans('voucher_sent.provider_scans') }}
    <br/>
    <br/>
    {{ mail_trans('voucher_sent.purchase_notice', ['voucher_amount' => $voucher_amount]) }}
    <br/>
    <br/>
    {{ mail_trans('voucher_sent.have_fund') }}
@endsection
