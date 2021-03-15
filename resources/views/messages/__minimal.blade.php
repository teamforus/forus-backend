<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title')</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: Montserrat;
            font-weight: 100;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .flex-column {
            flex-direction: column;
        }

        .position-ref {
            position: relative;
        }

        .title {
            font-weight: 500;
            font-size: 36px;
            line-height: 24px;
            text-align: center;
            color: #000;
        }

        .subtitle {
            padding-top: 20px;
            font-weight: normal;
            font-size: 18px;
            line-height: 24px;
            text-align: center;
            color: #000;
        }

        .image {
            display: block;
            margin: 20px auto;
        }
    </style>
</head>
<body>
<div class="flex-center flex-column position-ref full-height">
    <div class="title">
        @yield('title')
    </div>

    <div class="subtitle">
        @yield('subtitle')
    </div>

    <div class="image">
        <img src="@yield('image')">
    </div>
</div>
</body>
</html>
