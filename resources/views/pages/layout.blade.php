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

            .app-missing {}

            .app-missing p {
                display: none;
                font: 400 18px/24px sans-serif;
                margin: 0 0 20px;
            }

            .app-missing .button {
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
            }

            .button {
                padding: 5px 15px;
                box-shadow: 0 5px 65px rgba(0, 0, 0, 0.1);
                font-style: normal;
                font: 600 14px/36px "Helvetica";
                text-align: center;
                color: #FFFFFF;
                letter-spacing: 2px;
                text-decoration: none;
                background: #315EFD;
                border-radius: 6px;
                cursor: pointer;
                transition: all .4s;
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