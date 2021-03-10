@extends('emails.base')

@section('button_text', mail_trans('identity_email_verification.button_text'))
@section('link', $link)
@section('title', mail_trans('identity_email_verification.title'))
@section('header_image', mail_config('email_activation.header_image', null, $implementationKey ?? null))
@section('html')
    @if ($emailFrom->isInformalCommunication())
        {{ mail_trans('identity_email_verification.description_informal') }}
        {!! mail_trans('identity_email_verification.confirmation_button_informal', ['link' => $link]) !!} 
    @else
        {{ mail_trans('identity_email_verification.description_formal') }}
        {!! mail_trans('identity_email_verification.confirmation_button_formal', ['link' => $link]) !!} 
    @endif

    <br/>
@endsection
