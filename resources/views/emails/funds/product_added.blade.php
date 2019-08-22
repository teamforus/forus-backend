@extends('emails.base')
@section('title', mail_trans('product_added.title'))
@section('html')
    {{ mail_trans('dear_sponsor', ['sponsor_name' => $sponsor_name]) }}
    <br/>
    <br/>
    {{ mail_trans('product_added.new_product') }}
    <br/>
    <br/>
    {{ mail_trans('product_added.check_webshop', ['fund_name' => $fund_name]) }}
@endsection
