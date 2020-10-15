@extends('emails.base')

@section('title', mail_trans('voucher_assigned_subsidy.title', $data))
@section('html')
    {{ mail_trans('dear_user') }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned_subsidy.paragraph1', $data) }}
    <br>
    <br>
    {{ $data['fund_description_html'] ?? ''  }}
    <br>
    <br>
    {!! mail_trans('voucher_assigned_subsidy.paragraph2', $data) !!}
    <br/>
    <img style="display: block; margin: 0 auto;" alt="" src="{{ $message->embedData(make_qr_code('voucher', $data['qr_token']), 'qr_token.png') }}" width="300" />
    <br/>
    {{ mail_trans('voucher_assigned_subsidy.paragraph3', $data) }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned_subsidy.paragraph4', $data) }}
    <br/>
    <br/>
    {!! mail_trans('voucher_assigned_subsidy.paragraph5', $data) !!}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned_subsidy.paragraph6', $data) }}
    <br/>
    <br/>
    {{ mail_trans('voucher_assigned_subsidy.paragraph7', $data) }}
    <br/>
@endsection