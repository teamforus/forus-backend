@extends('emails.base')

@section('button_text', mail_trans('email_activation.button_text'))
@section('link', $link)
@section('title', mail_trans('email_activation.title'))
@section('header_image', mail_config('email_activation.header_image', null, $implementationKey ?? null))
@section('html')
    @if ($emailFrom->isInformalCommunication())
        {{ mail_trans('email_activation.you_get_this_mail_because_informal', ['platform' => mail_trans('email_activation.platforms.' . $clientType)]) }}.
    @else
        {{ mail_trans('email_activation.you_get_this_mail_because_formal', ['platform' => mail_trans('email_activation.platforms.' . $clientType)]) }}.
    @endif
    
    <br/>
    <br/>
    @if ($emailFrom->isInformalCommunication())
        {!! mail_trans('email_activation.confirmation_button_informal', ['link' => $link]) !!}.
    @else
        {!! mail_trans('email_activation.confirmation_button_formal', ['link' => $link]) !!}.
    <br/>
    @endif
@endsection
