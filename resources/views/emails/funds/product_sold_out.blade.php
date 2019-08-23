@extends('emails.base')
@section('title', mail_trans('product_sold_out.title', ['product_name' => $product_name]))
@section('button_text', 'Inloggen')
@section('link', $link)
@section('html')
    {{ mail_trans('dear_user') }}
    <br />
    <br />
    {{ mail_trans('product_sold_out.all_products_sold_out', ['product_name' => $product_name]) }}
    <br />
    {{ mail_trans('product_sold_out.fill_or_remove') }}
    <br />
    <br />
    {!! mail_trans('product_sold_out.dashboard_button', ['link' => $link]) !!}
    <br/>
@endsection
