@extends('emails.base')
@section('title', mail_trans('product_bought.title', ['product_name' => $product_name]))
@section('html')
    {{ mail_trans('dear_user') }}
    <br/>
    <br/>
    <br/>
    {{ mail_trans('product_bought.product_reserved', ['product_name' => $product_name]) }}
    <br/>
    <br/>
    {{ mail_trans('product_bought.deadline', ['expiration_date' => $expiration_date]) }}
    <br/>
    <br/>
    {{ mail_trans('hopefully_informed_enough') }}
    <br/>
@endsection
