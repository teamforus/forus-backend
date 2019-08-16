@extends('emails.base')

@section('title', implementation_trans('new_validation_request.title'))
@section('html')
    {{ implementation_trans('dear_validator') }}
    <br/>
    <br/>
    {{ implementation_trans('new_validation_request.request_ready') }}
    <br/>
    <br/>
    {!! implementation_trans('new_validation_request.dashboard_button', ['link' => $validator_dashboard_link]) !!}
@endsection
