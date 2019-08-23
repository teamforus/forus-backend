@extends('emails.base')

@section('title', mail_trans('voucher_sent.title', ['fund_name' => $fund_name]))
@section('header_image', 'https://www.stickpng.com/assets/images/5841b969a6515b1e0ad75a93.png')

@section('html')
    {{ mail_trans('dear_user') }}
    <br/>
    <br/>
    {{ mail_trans('voucher_sent.you_have_asked', ['product_name' => $fund_product_name]) }}
    <br/>
    {{ mail_trans('voucher_sent.qr_code_under') }}
    <br/>
    {{ mail_trans('voucher_sent.provider_scans') }}
    <br/>
    <br/>
    <img style="display: block; margin: 0 auto;" src="{{ $qr_url }}" width="300" />
@endsection
