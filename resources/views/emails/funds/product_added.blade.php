@extends('emails.base')
@section('title', implementation_trans('product_added.title'))
@section('html')
    {{ implementation_trans('dear_sponsor', ['sponsor_name' => $sponsor_name]) }}
    <br/>
    <br/>
    {{ implementation_trans('product_added.new_product') }}
    <br/>
    <br/>
    {{ implementation_trans('product_added.check_webshop', ['fund_name' => $fund_name]) }}
@endsection
