@extends('emails.base')

@section('button_text', mail_trans('provider_invitation.button_text'))
@section('link', $invitation_link)
@section('title', mail_trans('provider_invitation.title', [
    'sponsor_name' => $sponsor_name,
    'fund_name' => $fund_name
]))

<?php
    $allVars = compact('provider_name', 'sponsor_name', 'sponsor_email', 'sponsor_phone', 'fund_name', 'fund_start_date', 'fund_end_date', 'from_fund_name');
?>

@section('html')
    {{ mail_trans('dear_provider', ['provider_name' => $provider_name]) }},
    <br/>
    <br/>
    {!! nl2br(e(mail_trans('provider_invitation.invitation_text', $allVars))) !!},
    <br/>
    <br/>
    {!! mail_trans('provider_invitation.accept_invitation', ['link' => $invitation_link]) !!}
@endsection
