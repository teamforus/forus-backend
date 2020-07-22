@extends('emails.base')

{{--@section('button_text', mail_trans('email_employee.button_text'))--}}
{{--@section('link', $link)--}}
@section('title', mail_trans('email_employee.title', ['organization_name' => $organization_name]))
@section('header_image', mail_config('email_activation.header_image'))
@section('html')
    {{ mail_trans('email_employee.invitation_for', ['organization_name' => $organization_name]) }}
    <br/>
    <br/>
    {!! mail_trans('email_employee.create_account', ['confirmationLink' => $confirmationLink]) !!}
    <br/>
    <br/>
    {!! mail_trans('email_employee.create_profile', ['link' => $link]) !!}
    <br/>
@endsection
