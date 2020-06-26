@extends('emails.base')

@section('button_text', mail_trans('identity_email_verification.button_text'))
@section('link', $link)
@section('title', mail_trans('identity_email_verification.title'))
@section('header_image', mail_config('email_activation.header_image'))
@section('html')
    {{ mail_trans('identity_email_verification.description') }}
    {!! mail_trans('identity_email_verification.confirmation_button', $link) !!}
    <br/>
@endsection
