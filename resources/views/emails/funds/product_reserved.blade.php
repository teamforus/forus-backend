<?php
/** @var string $provider_organization_name */
/** @var string $product_price */
/** @var string $product_name */
/** @var bool $informalCommunication */
/** @var string $communicationType */
/** @var \App\Services\Forus\Notification\EmailFrom $emailFrom */
?>

@extends('emails.base')
@section('title', mail_trans("product_reserved.title_$communicationType", [
    'product_name' => $product_name,
    'provider_organization_name' => $provider_organization_name
]))
@section('html')
    {{ mail_trans('dear_user') }},
    <br/>
    <br/>
    <br/>
    {{ mail_trans('product_reserved.product_reserved', [
        'product_name' => $product_name,
        'provider_organization_name' => $provider_organization_name
    ]) }}
    <br/>
    <br/>
    <span style="text-align: center; font-size: 24px;">{{ $product_name }}</span>
    <br/>
    <img style="display: block; margin: 0 auto;" alt="" src="{{ $message->embedData(make_qr_code('voucher', $qr_token), 'qr_token.png') }}" width="300" />
    <br/>
    {{ mail_trans('product_reserved.deadline', [
        'price' => $product_price,
        'expire_at_minus_1_day' => $expire_at_locale,
    ]) }}
    <br/>
    <br/>
    {!! mail_trans('product_reserved.contact_us', [
        'provider_organization_name' => $provider_organization_name,
        'provider_phone' => $provider_phone,
        'provider_email' => $provider_email,
    ]) !!}
    <br/>
@endsection
