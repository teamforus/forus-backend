<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>{{ config('app.name') }}</title>
        <link href="{{ asset('/assets/dist/css/materialdesignicons.min.css') }}" rel="stylesheet">

        @yield('styles')
    </head>
<body>
    @yield('content')
    @yield('scripts')
</body>
</html>