@extends('emails.base')

@section('button_text', implementation_trans('email_activation.button_text'))
@section('link', $link)
@section('title', implementation_trans('email_activation.title'))
@section('header_image', implementation_config('email_activation.header_image'))
@section('html')
    {{ implementation_trans('email_activation.you_get_this_mail_because') }}
    <br/>
    <br/>
    {!! implementation_trans('email_activation.confirmation_button', ['link' => $link]) !!}
    <br/>
@endsection
