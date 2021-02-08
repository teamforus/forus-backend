@extends('emails.base')

@section('button_text', mail_trans('login_via_email.button_text'))
@section('link', $link)
@section('title', mail_trans('login_via_email.title', ['platform' => $platform]))
@section('header_image', mail_config('login_via_email.header_image'))

@section('html')
    {{ mail_trans('dear_user') }},
    <br/>
    <br/>
    @if ($emailFrom->isInformalCommunication())
        {{ mail_trans('login_via_email.login_on_platform_informal', ['platform' => $platform]) }}.
    @else
        {{ mail_trans('login_via_email.login_on_platform_formal', ['platform' => $platform]) }}.
    @endif
    <br />
    {!! mail_trans('login_via_email.login_button', ['link' => $link]) !!}
    <br/>
    <br/>
    {{ mail_trans('login_via_email.login_expire', ['time' => strftime('%e %B %H:%M', strtotime("+1 hours"))]) }}
@endsection

