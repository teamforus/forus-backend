@extends('emails.base')
@section('title', implementation_trans('product_sold_out.title', ['product_name' => $product_name]))
@section('button_text', 'Inloggen')
@section('link', $link)
@section('html')
    {{ implementation_trans('dear_user') }}
    <br />
    <br />
    {{ implementation_trans('product_sold_out.all_products_sold_out', ['product_name' => $product_name]) }}
    <br />
    {{ implementation_trans('product_sold_out.fill_or_remove') }}
    <br />
    <br />
    {{ implementation_trans('product_sold_out.dashboard_button', ['link' => $link]) }}
    <br/>
@endsection
