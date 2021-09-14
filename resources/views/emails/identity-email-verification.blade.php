@extends('emails.base')

@section('button_text', trans('mails/identity_email_verification.button_text'))
@section('link', $link)
@section('title', trans('mails/identity_email_verification.title'))
@section('header_image', mail_config('email_activation.header_image', null, $implementationKey ?? null))
@section('html')
    @if ($emailFrom->isInformalCommunication())
        {{ trans('mails/identity_email_verification.description_informal') }}
        {!! trans('mails/identity_email_verification.confirmation_button_informal', ['link' => $link]) !!}
    @else
        {{ trans('mails/identity_email_verification.description_formal') }}
        {!! trans('mails/identity_email_verification.confirmation_button_formal', ['link' => $link]) !!}
    @endif

    <br/>
@endsection
