<?php
    /** @var string $fund_name */
    /** @var string $webshop_link_clarification Link to clarification answer page */
    /** @var string $question Question asked by validator */
    $viewData = compact('fund_name', 'question', 'webshop_link_clarification');
?>
@extends('emails.base')
@if ($emailFrom->isInformalCommunication())
    @section('button_text', mail_trans('fund_request_clarification_requested.button_text_informal'))
@else
    @section('button_text', mail_trans('fund_request_clarification_requested.button_text_formal'))  
@endif
@section('link', $webshop_link_clarification)
@if ($emailFrom->isInformalCommunication())
    @section('title', mail_trans('fund_request_clarification_requested.title_informal', ['fund_name' => $fund_name]))    
@else
    @section('title', mail_trans('fund_request_clarification_requested.title_formal', ['fund_name' => $fund_name]))
@endif
@section('html')
    {{ $question }}
    <br/>
    <br/>
@endsection
