@extends('pages.layout')

@section('content')
    <div class="wrapper">

        <div id="root"></div>

        <div id="params"
             data-exchange-token="{{ $exchangeToken ?? '' }}"
             data-type="{{ $type }}"
             data-mobile="{{ request()->userAgent() && user_agent_data(request()->userAgent())->isMobile() ? 'true' : 'false' }}"
             data-api-url="{{ url('api/v1') }}"
             data-client-type="{{ $clientType ?? '' }}"
             data-client-key="{{ $implementationKey ?? '' }}"
        />
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('/assets/js/app.js?time=' . time()) }}"></script>
@endsection
