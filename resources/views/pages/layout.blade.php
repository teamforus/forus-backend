<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>{{ config('app.name') }}</title>
        <link rel="stylesheet" href="{{ asset('/assets/dist/bundle/css/bundle.min.css') }}">
        <link rel="stylesheet" href="{{ asset('/assets/css/style.min.css?time=' . time()) }}">
    </head>
<body modal-scroll-breaker>
    @yield('content')
    @yield('scripts')
</body>
</html>