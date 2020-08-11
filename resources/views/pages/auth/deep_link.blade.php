@extends('pages.layout')

@section('content')
    @if(user_agent_data(request()->userAgent())->isMobile())
        <div class="block block-missing-app">
            <p>Open deze link op het zelfde apparaat waar het aanmeldverzoek is aangevraagd.</p>
            <a class="button button-primary" href="{{ $redirectUrl }}" onclick="onClick()">OPEN ME APP</a>
        </div>
        <script>
            onClick = function() {
                let showMessage = function () {
                    document.querySelector('.block-missing-app p').style.display = "block";
                };
                setTimeout(showMessage, 2500);
            };

            setTimeout(function() {
                document.querySelector('.block-missing-app .button').click();
            }, 100);
        </script>
    @else
        <div class="wrapper" ng-controller="BaseController">
            <auth-pin-code type="{{ $type ?? '' }}" token="{{ $exchangeToken ?? '' }}"></auth-pin-code>
        </div>
    @endif
@endsection

@section('scripts')
    <script src="{{ asset('/assets/dist/bundle/js/bundle.min.js') }}"></script>
    <script src="{{ asset('/assets/js/app.min.js?time=' . time()) }}"></script>
@endsection
