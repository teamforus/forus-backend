@extends('pages.layout')

@section('content')
    <p class="app-missing">Please install me.app on this device first.</p>
    <p>
        <a class="button" href="{{ $redirectUrl }}" onclick="onClick()">Open me.app</a>
    </p>
@endsection

@section('scripts')
    <script>
        onClick = function() {
            let showMessage = function () {
                document.querySelector('.app-missing').style.display = "block";
            };
            setTimeout(showMessage, 2500);
        }
    </script>
@endsection