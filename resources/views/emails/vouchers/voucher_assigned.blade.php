@extends('emails.base')

@section('title', mail_trans('voucher_assigned.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_user') }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned.you_have_been_assigned', ['fund_name' => $fund_name]) }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned.voucher_details', [
        'voucher_amount'      => $voucher_amount,
        'expire_at_minus_day' => $voucher_expire_minus_day
    ]) }}
    <br/>
    <img style="display: block; margin: 0 auto;" alt="" src="{{ $message->embedData(make_qr_code('voucher', $qr_token), 'qr_token.png') }}" width="300" />
    <br/>
    {{ mail_trans('voucher_assigned.automatic_payment') }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned.purchase_notice', ['voucher_amount' => $voucher_amount]) }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned.have_fund') }}
    <br/>
@endsection