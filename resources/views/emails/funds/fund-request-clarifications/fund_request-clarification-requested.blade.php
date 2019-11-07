<?php
    /** @var string $fund_name */
    /** @var string $webshop_link Link to webshop */
    /** @var string $webshop_link_clarification Link to clarification answer page */
    /** @var string $question Question asked by validator */
    $viewData = compact('fund_name', 'webshop_link', 'question', 'webshop_link_clarification');
?>
@extends('emails.base')

@section('title', mail_trans('fund_request_clarification_requested.title', ['fund_name' => $fund_name]))
@section('html')
    {{ mail_trans('dear_user') }}
    <br/>
    <br/>
    {{ mail_trans('fund_request_clarification_requested.message', ['question' => $question, 'fund_name' => $fund_name]) }}
    <br />
    {{ mail_trans('fund_request_clarification_requested.question', ['question' => $question]) }}
    <br />
    <a href="{{ $webshop_link_clarification }}">{{ $webshop_link_clarification }}</a>
    <br/>
    <br/>
    {!! mail_trans('fund_request_clarification_requested.webshop_button', ['link' => $webshop_link]) !!}
@endsection
