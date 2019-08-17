@extends('emails.base')

@section('title', mail_trans('you_added_as_validator.title'))
@section('html')
    {{ mail_trans('dear_coworker') }}
    <br/>
    <br/>
    {{ mail_trans('you_added_as_validator.added_by_sponsor', ['sponsor_name' => $sponsor_name]) }}
    <br/>
    {{ mail_trans('you_added_as_validator.from_now_on') }}
    <br/>
@endsection
