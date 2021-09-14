<?php
/** @var string $communicationType */
/** @var string $subject */
/** @var array $data */
?>

@extends('emails.base')
@section('title', trans('mails/provider_invitation.title', $subject))
@section('button_text', trans('mails/provider_invitation.button_text', $data))
@section('link', $invitation_link)

@section('html')
    {!! trans('mails/_misc.dear_provider', $data) !!},
    <br/>
    <br/>
    {!! trans('mails/provider_invitation.invitation_text', $data) !!},
    <br/>
    <br/>
    {!! trans('mails/provider_invitation.accept_invitation', $data) !!}
@endsection
