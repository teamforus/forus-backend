@extends('emails.base')

@if ($emailFrom->isInformalCommunication())
    @section('title', mail_trans('voucher_sent.title_informal', ['fund_name' => $fund_name]))
@else
    @section('title', mail_trans('voucher_sent.title_formal', ['fund_name' => $fund_name])) 
@endif
@section('html')
    {{ mail_trans('dear_user') }}
    <br/>
    <br/>
    @if ($emailFrom->isInformalCommunication())
        {{ mail_trans('voucher_sent.you_have_asked_informal', ['product_name' => $fund_product_name]) }}
        <br/>
        <br/>
        {{ mail_trans('voucher_sent.qr_code_under_informal',[
            'voucher_amount'      => $voucher_amount,
            'expire_at_minus_day' => $voucher_last_active_day
        ]) }}
        <br/>
        <img style="display: block; margin: 0 auto;" alt="" src="{{ $message->embedData(make_qr_code('voucher', $qr_token), 'qr_token.png') }}" width="300" />
        <br/>
        {{ mail_trans('voucher_sent.provider_scans') }}
        <br/>
        <br/>
        {{ mail_trans('voucher_sent.have_fund_informal') }} 
    @else
        {{ mail_trans('voucher_sent.you_have_asked_formal', ['product_name' => $fund_product_name]) }}
        <br/>
        <br/>
        {{ mail_trans('voucher_sent.qr_code_under_formal',[
            'voucher_amount'      => $voucher_amount,
            'expire_at_minus_day' => $voucher_last_active_day
        ]) }}
        <br/>
        <img style="display: block; margin: 0 auto;" alt="" src="{{ $message->embedData(make_qr_code('voucher', $qr_token), 'qr_token.png') }}" width="300" />
        <br/>
        {{ mail_trans('voucher_sent.provider_scans') }}
        <br/>
        <br/>
        {{ mail_trans('voucher_sent.have_fund_formal') }}
    @endif

@endsection
