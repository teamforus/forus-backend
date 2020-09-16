@extends('emails.base')

@section('title', mail_trans('product_actions_removed.title', [
    'provider_name' => $provider_name,
    'product_name'  => $product_name
]))

@section('html')
    {{ mail_trans('dear_sponsor', ['sponsor_name' => $sponsor_name]) }},
    <br/>
    <br/>
    {{ mail_trans('product_actions_removed.actions_disabled', [
        'product_name'  => $product_name,
        'provider_name' => $provider_name,
    ]) }}
    <br/>
    <br/>
    {!! mail_trans('product_actions_removed.reapprove_on_dashboard', ['link' => $sponsor_dashboard_link]) !!}
@endsection