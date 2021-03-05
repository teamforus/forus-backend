@extends('emails.base')

@if ($emailFrom->isInformalCommunication())
    @section('title', mail_trans('voucher_assigned_subsidy.title_informal', $data))
@else
    @section('title', mail_trans('voucher_assigned_subsidy.title_formal', $data))
@endif
@section('html')
    {{ mail_trans('dear_user') }}
    <br/>
    <br/>
    @if ($emailFrom->isInformalCommunication())
        {{ mail_trans('voucher_assigned_subsidy.paragraph1_informal', $data) }}
        <br>
        <br>
        {!! mail_trans('voucher_assigned_subsidy.paragraph2_informal', $data) !!}
        <br/>
        <img style="display: block; margin: 0 auto;" alt="" src="{{ $message->embedData(make_qr_code('voucher', $data['qr_token']), 'qr_token.png') }}" width="300" />
        <br/>
        {{ mail_trans('voucher_assigned_subsidy.paragraph3', $data) }}
        <br/>
        <br/>
        {{ mail_trans('voucher_assigned_subsidy.paragraph4_informal', $data) }}
        <br/>
        <br/>
        {!! mail_trans('voucher_assigned_subsidy.paragraph5_informal', $data) !!}
        <br/>
        <br/>
        {{ mail_trans('voucher_assigned_subsidy.paragraph6_informal', $data) }}
        <br/>
        <br/>
        {{ mail_trans('voucher_assigned_subsidy.paragraph7_informal', $data) }}
    @else
        {{ mail_trans('voucher_assigned_subsidy.paragraph1_formal', $data) }}
        <br>
        <br>
        {!! mail_trans('voucher_assigned_subsidy.paragraph2_formal', $data) !!}
        <br/>
        <img style="display: block; margin: 0 auto;" alt="" src="{{ $message->embedData(make_qr_code('voucher', $data['qr_token']), 'qr_token.png') }}" width="300" />
        <br/>
        {{ mail_trans('voucher_assigned_subsidy.paragraph3', $data) }}
        <br/>
        <br/>
        {{ mail_trans('voucher_assigned_subsidy.paragraph4_formal', $data) }}
        <br/>
        <br/>
        {!! mail_trans('voucher_assigned_subsidy.paragraph5_formal', $data) !!}
        <br/>
        <br/>
        {{ mail_trans('voucher_assigned_subsidy.paragraph6_formal', $data) }}
        <br/>
        <br/>
        {{ mail_trans('voucher_assigned_subsidy.paragraph7_formal', $data) }}  
    @endif


    <br/>
@endsection