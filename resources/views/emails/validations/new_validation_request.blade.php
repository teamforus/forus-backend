@extends('emails.base)

@section('title', trans('mails.validations.new_validation_request.title'))
@section('html')
    Beste validator,
    <br/>
    <br/>
    Er staat een verzoek voor u klaar om eigenschappen te valideren.
    <br/>
    <br/>
    Ga naar het validator dashboard <a href="{{ $validator_dashboard_link }}" target="_blank" style="color: #315efd; text-decoration: underline;">{{ $validator_dashboard_link }}</a> om dit verzoek te behandelen.
@endsection
