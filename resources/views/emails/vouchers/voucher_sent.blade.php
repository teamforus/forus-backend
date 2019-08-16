@extends('emails.base')

@section('title', implementation_trans('voucher_sent.title', ['fund_name' => $fund_name]))
@section('html')
    {{ implementation_trans('dear_user') }}
    <br/>
    <br/>
    {{ implementation_trans('voucher_sent.you_have_asked', ['product_name' => $fund_product_name]) }}
    <br/>
    {{ implementation_trans('voucher_sent.qr_code_under') }}
    <br/>
    {{ implementation_trans('voucher_sent.provider_scans') }}
    <br/>
    <br/>
    <img style="display: block; margin: 0 auto;" src="{{ $qr_url }}" width="300" />
@endsection
