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
        }
        .app-missing {
            display: none;
        }

        .button {
            background: rgb(17, 134, 191);
            border: none;
            color: #fff;
            font: 500 14px sans-serif;
            padding: 10px 30px;
            border-radius: 3px;
            cursor: pointer;
            transition: .4s;
            outline: none;
            box-shadow: 2px 4px 20px rgba(0,0,0,.1);
            text-decoration: none;
        }

        .button:hover {
            background: rgb(41, 170, 234);
            box-shadow: 2px 4px 10px rgba(0,0,0,.1);
        }

    </style>
</head>
<body>
    <p class="app-missing">Please install me.app on this device first.</p>
    <p>
        <a class="button" href="{{ $redirectUrl }}" onclick="onClick()">Open me.app</a>
    </p>
    <script>
        onClick = function() {
            let showMessage = function () {
                document.querySelector('.app-missing').style.display = "block";
            };
            setTimeout(showMessage, 2500);
        }
    </script>
</body>
</html>