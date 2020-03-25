@extends('pages.layout')

@section('content')
    <div class="app-missing">
        <p>This link is meant to be opened on the same device used to request authentication by email where you already have "me.app" installed.</p>
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
