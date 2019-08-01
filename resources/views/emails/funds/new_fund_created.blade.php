@extends('emails.base)

@section('title', trans('mails.funds.new_fund_created.title'))
@section('html')
    Beste inwoner,
    <br/>
    <br/>
    Er is een nieuw fonds aangemaakt. Uw voldoet aan de voorwaarden om mee te doen aan {{ $fund_name }}.
    <br/>
    <br/>
    Ga naar <a href="{{ $webshop_link }}" target="_blank" style="color: #315efd; text-decoration: underline;">{{ $webshop_link }}</a>, log in op de webshop om u aan te melden voor {{ $fund_name }}.
@endsection
