@extends('emails.base')

@section('title', mail_trans('voucher_assigned_product.title', $data))
@section('html')
    {{ mail_trans('dear_user') }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned_product.paragraph1', $data) }}
    <br/>
    <br/>
    {{ $data['product_description'] ?? '' }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned_product.paragraph2', $data) }}
    <br/>
    <img style="display: block; margin: 0 auto;" alt="" src="{{ $message->embedData(make_qr_code('voucher', $data['qr_token']), 'qr_token.png') }}" width="300" />
    <br/>
    {{ mail_trans('voucher_assigned_product.paragraph3', $data) }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned_product.paragraph4', $data) }}
    <br/>
@endsection