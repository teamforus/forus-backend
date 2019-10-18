<?php
    /** @var string $fund_name */
    /** @var string $webshop_link Link to webshop */
    /** @var string $webshop_link_clarification Link to clarification answer page */
    /** @var string $question Question asked by validator */
    $viewData = compact('fund_name', 'webshop_link', 'question', 'webshop_link_clarification');
?>
@extends('emails.base')

@section('title', mail_trans('fund_request_clarification_requested.title', $viewData))
@section('html')
    {{ mail_trans('dear_citizen', $viewData) }}
    <br/>
    <br/>
    {{ mail_trans('fund_request_clarification_requested.message', $viewData) }}
    {{ json_encode_pretty($viewData) }}
    <a href="{{ $webshop_link_clarification }}">Clarification link</a>
    <br/>
    <br/>
    {!! mail_trans('fund_request_clarification_requested.webshop_button', $viewData) !!}
@endsection
