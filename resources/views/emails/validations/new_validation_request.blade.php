@extends('emails.base')

@section('title', mail_trans('new_validation_request.title'))
@section('html')
    {{ mail_trans('dear_validator') }}
    <br/>
    <br/>
    {{ mail_trans('new_validation_request.request_ready') }}
    <br/>
    <br/>
    {!! mail_trans('new_validation_request.dashboard_button', ['link' => $validator_dashboard_link]) !!}
@endsection
