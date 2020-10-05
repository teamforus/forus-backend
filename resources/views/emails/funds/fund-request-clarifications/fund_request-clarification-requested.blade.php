<?php
    /** @var string $fund_name */
    /** @var string $webshop_link_clarification Link to clarification answer page */
    /** @var string $question Question asked by validator */
    $viewData = compact('fund_name', 'question', 'webshop_link_clarification');
?>
@extends('emails.base')

@section('button_text', mail_trans('fund_request_clarification_requested.button_text'))
@section('link', $webshop_link_clarification)
@section('title', mail_trans('fund_request_clarification_requested.title', ['fund_name' => $fund_name]))
@section('html')
    {{ $question }}
    <br/>
    <br/>
@endsection
