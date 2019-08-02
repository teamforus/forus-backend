@extends('emails.base')
@section('title', implementation_trans('product_bought.title', ['product_name' => $product_name]))
@section('html')
    {{ implementation_trans('dear_user') }}
    <br/>
    <br/>
    <br/>
    {{ implementation_trans('product_bought.product_reserved', ['product_name' => $product_name]) }}
    <br/>
    <br/>
    {{ implementation_trans('product_bought.deadline', ['expiration_date' => $expiration_date]) }}
    <br/>
    <br/>
    {{ implementation_trans('hopefully_informed_enough') }}
    <br/>
@endsection
