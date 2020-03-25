@extends('pages.layout')

@section('content')
    <div class="app-missing">
        <p>Open deze link op het zelfde apparaat waar het aanmeldverzoek is aanvraagd.</p>
        <a class="button" href="{{ $redirectUrl }}" onclick="onClick()">OPEN ME APP</a>
    </div>
@endsection

@section('scripts')
    <script>
        onClick = function() {
            let showMessage = function () {
                document.querySelector('.app-missing p').style.display = "block";
            };
            setTimeout(showMessage, 2500);
        };

        setTimeout(function() {
            document.querySelector('.app-missing .button').click();
        }, 100);
    </script>
@endsection
