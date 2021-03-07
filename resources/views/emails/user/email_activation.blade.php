@extends('emails.base')

@section('button_text', mail_trans('email_activation.button_text'))
@section('link', $link)
@section('title', mail_trans('email_activation.title'))
@section('header_image', mail_config('email_activation.header_image', null, $implementationKey ?? null))
@section('html')
    {{ mail_trans('email_activation.you_get_this_mail_because', ['platform' => mail_trans('email_activation.platforms.' . $clientType)]) }}
    <br/>
    <br/>
    {!! mail_trans('email_activation.confirmation_button', ['link' => $link]) !!}
    <br/>
@endsection
