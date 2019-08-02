@extends('emails.base)

@section('title', implementation_trans('you_added_as_validator.title'))
@section('html')
    {{ implementation_trans('dear_coworker') }}
    <br/>
    <br/>
    {{ implementation_trans('you_added_as_validator.added_by_sponsor', ['sponsor_name' => $sponsor_name]) }}
    <br/>
    {{ implementation_trans('you_added_as_validator.from_now_on') }}
    <br/>
@endsection
