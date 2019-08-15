@extends('emails.base')

@section('button_text', implementation_trans('login_via_email.button_text'))
@section('link', $link)
@section('title', implementation_trans('login_via_email.title', ['platform' => $platform]))
@section('header_image', implementation_config('login_by_email.header_image'))

@section('html')
    {{ implementation_trans('dear_user') }},
    <br/>
    <br/>
    {{ implementation_trans('login_via_email.login_on_platform', ['platform' => $platform]) }}.
    <br/>
    {!! implementation_trans('login_via_email.login_button', ['link' => $link]) !!}
    <br/>
@endsection

