@extends('pages.layout')

@section('content')
    <div class="wrapper" ng-controller="BaseController">
        <auth2-f-a-component
            token="{{ $exchangeToken ?? '' }}"
            type="{{ $type }}"
            mobile="{{ request()->userAgent() && user_agent_data(request()->userAgent())->isMobile() ? 'true' : 'false' }}" />
    </div>

    <modals-root />
@endsection

@section('scripts')
    <script src="{{ asset('/assets/dist/bundle/js/bundle.min.js') }}"></script>
    <script src="{{ asset('/assets/js/app.min.js?time=' . time()) }}"></script>
@endsection
