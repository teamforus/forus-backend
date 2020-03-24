<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Document</title>
        <style>
            body {
                padding: 30px;
                margin: 0;
            }
            p {
                font: 400 14px sans-serif;
                text-align: center;
                margin: 0 0 20px;
            }

            .title {
                font: 400 22px/36px sans-serif;
                margin: 0 0 20px;
            }

            .text-center {
                text-align: center;
            }

            .app-missing {
                display: none;
            }

            .button {
                position: absolute;
                bottom: 0;
                top: 0;
                left: 0;
                right: 0;
                margin: auto;
                width: 166px;
                height: 46px;
                box-shadow: 0 5px 65px rgba(0, 0, 0, 0.1);
                font-style: normal;
                font: 600 14px/46px "Helvetica";
                text-align: center;
                color: #FFFFFF;
                letter-spacing: 2px;
                text-transform: uppercase;
                text-decoration: none;
                background: #315EFD;
                border-radius: 6px;
                padding: 0;
                cursor: pointer;
            }

            .button:hover {
                background: rgb(41, 170, 234);
                box-shadow: 2px 4px 10px rgba(0,0,0,.1);
            }

        </style>
    </head>
<body>
    @yield('content')
    @yield('scripts')
</body>
</html>