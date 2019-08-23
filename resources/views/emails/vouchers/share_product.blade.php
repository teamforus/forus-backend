@extends('emails.base')
@section('title', mail_trans('share_product.title', ['requester_email' => $requester_email]))

@section('html')
    {{ $product_name }}
    <br />
    <img style="display: block; margin: 0 auto;" src="{{ $qr_url }}" width="300" />
    <br />
    {{ mail_trans('share_product.has_shared_with_message', ['requester_email' => $requester_email]) }}
    <br />
    {{ $reason }}
@endsection
