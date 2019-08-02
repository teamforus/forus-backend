@extends('emails.base)

@section('title', trans('mails.validations.you_added_as_validator.title'))
@section('html')
    Beste medewerker,
    <br/>
    <br/>
    {{ $sponsor_name }} heeft u toegevoegd als validator.
    <br/>
    Vanaf nu kunt u aanvragers toevoegen, dit kunt u doen door naar het dashboard te gaan een een .CSV bestand te uploaden.
    <br/>
    @implementation('login_by_email.css.header')
@endsection
