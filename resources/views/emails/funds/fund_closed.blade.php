@extends('emails.base')

@if ($emailFrom->isInformalCommunication())
    @section('title', mail_trans('fund_closed.title_informal', ['fund_name' => $fund_name])).
@else
    @section('title', mail_trans('fund_closed.title_formal', ['fund_name' => $fund_name])).
@endif
@section('html')
    {{ mail_trans('dear_citizen') }},
    <br />
    <br />
    @if ($emailFrom->isInformalCommunication())
        {{ mail_trans('fund_closed.description_informal', [
            'fund_name'    => $fund_name,
            'sponsor_name' => $sponsor_name,
        ]) }} <br />
        {{ mail_trans('fund_closed.contact_informal', [
            'fund_name'    => $fund_name,
            'fund_contact' => $fund_contact,
        ]) }} <br />
    @else
        {{ mail_trans('fund_closed.description_formal', [
            'fund_name'    => $fund_name,
            'sponsor_name' => $sponsor_name,
        ]) }} <br />
        {{ mail_trans('fund_closed.contact_formal', [
            'fund_name'    => $fund_name,
            'fund_contact' => $fund_contact,
        ]) }} <br />
    @endif
    <br/>
    {!! mail_trans('fund_closed.webshop_link', ['link' => $webshop_link]) !!}
@endsection

