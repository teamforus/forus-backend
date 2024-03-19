<?php
/** @var array $funds */
$fontFamily = " Helvetica, sans-serif !important";
$squareLogo = $implementation_key === 'eemsdelta';


$borderColors = match ($implementation_key) {
    'eemsdelta' => ['#004680', '#80a2c0', '#a5bfd3'],
    'potjeswijzer' => ['#c0c927', '#dfe492', '#e8ecb3'],
    default => ['#315efd', '#99aefe', '#b7c6ff'],
};

$baseColor = $borderColors[0] ?? '#cecece';
?>

        <!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title></title>
</head>

{{-- Header styles --}}
<style>
    body {
        margin: 115px 0 40px;
        padding: 0;
    }

    header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
    }

    .header-content {
        position: relative;
        height: 80px;
        width: 100%;
        margin: 0 0 12px;
    }

    .header-content .header-content-logo {
        width: {{ $squareLogo ? '80px' : '28%' }};
        height: 80px;
        float: left;
        margin-top: -5px;
        position: relative;
    }

    .header-content .header-content-logo img {
        max-width: 100%;
        max-height: 100%;
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }

    .header-content .header-content-separator {
        display: block;
        float: left;
        height: 80px;
        width: 10%;
        position: relative;
    }

    .header-content .header-content-separator .header-content-separator-line {
        display: block;
        content: '';
        position: absolute;
        width: 1px !important;
        left: 50%;
        top: 50%;
        background: #bebebe;
        transform: translate(-50%, -50%);
        height: 60%;
    }

    .header-content .header-content-date {
        width: 28%;
        height: 80px;
        float: left;
        position: relative;
    }

    .header-content .header-content-date .header-content-date-title {
        font: 700 15px/24px{{ $fontFamily }};
        margin: 20px 0 0;
    }

    .header-content .header-content-date .header-content-date-description {
        font: 400 11px/16px{{ $fontFamily }};
        margin: 0 0;
    }

    .header-separators {
        width: 100%;
        height: 2px;
        position: relative;
    }

    .header-separators .header-separator {
        display: block;
        width: 49.5%;
        left: 0;
        height: 2px;
        background: #000;
        border-radius: 3px;
        position: absolute;
    }

    .header-separators .header-separator:last-child {
        background: #CDCED2;
        left: 50.5%;
    }
</style>

{{-- Footer styles --}}
<style>
    footer {
        display: block;
        position: fixed;
        bottom: -20px;
        left: 0;
        right: 0;
    }

    .footer-content {
        position: relative;
        width: 100%;
        height: 50px;
        display: flex;
    }

    .footer-content .footer-content-logo {
        display: flex;
        flex: 0 0 30px;
        height: 40px;
        width: {{ $squareLogo ? '40px' : '120px' }};
        float: left;
        position: relative;
    }

    .footer-content .footer-content-logo img {
        max-width: 100%;
        max-height: 100%;
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }

    .footer-content .footer-content-separator {
        display: block;
        float: left;
        height: 40px;
        width: 5%;
        position: relative;
    }

    .footer-content .footer-content-separator .footer-content-separator-line {
        display: block;
        content: '';
        position: absolute;
        width: 1px !important;
        left: 50%;
        top: 50%;
        background: #bebebe;
        transform: translate(-50%, -50%);
        height: 40%;
    }

    .footer-content .footer-content-date {
        float: left;
        font: 400 12px/20px{{ $fontFamily }};
        padding: 10px 0;
    }

    .footer-content .footer-content-page {
        float: right;
        font: 400 12px/20px{{ $fontFamily }};
        padding: 10px 0;
    }

    .footer-content .footer-content-page:before {
        content: "Pagina " counter(page);
        font: 400 12px/18px{{ $fontFamily }};
    }
</style>


{{-- Main styles --}}
<style>
    h1 {
        font: 700 18px/28px{{ $fontFamily }};
        margin: 0 0 15px;
    }

    p {
        font: 400 15px/20px{{ $fontFamily }};
        margin: 0 0 15px;
    }

    table {
        border-spacing: 3px;
        margin-bottom: 10px;
        width: 100%;

        tr {
            th, td {
                text-align: left;
                border-collapse: collapse;
            }

            th {
                text-align: left;
                white-space: nowrap;
                padding: 10px;
                background-color: #F4F5F7;
                border-bottom: 2px solid{{$baseColor}};
                border-right: 2px solid #fff;
                font: 700 13px/18px{{ $fontFamily }};
            }

            td {
                padding: 10px;
                font: 400 14px/18px{{ $fontFamily }};
                border-bottom: 1px solid #000;
            }
        }
    }

    .notice-block {
        background-color: #F4F5F7;
        padding: 20px;
        font: 400 15px/18px{{ $fontFamily }};
        margin-bottom: 30px;
    }

    .list-item {
        font: 400 13px/19px{{ $fontFamily }};
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #9E9E9E;
    }

    .list-item .list-item-title {
        margin-bottom: 10px;
    }

    .list-item .list-item-description {
        padding-left: 20px;
    }

    .list-item .list-item-title h2 {
        display: inline-block;
        font: 700 16px/20px{{ $fontFamily }};
        margin: 0;
    }

    .list-item .list-item-title:before {
        content: "";
        display: inline-block;
        height: 6px;
        width: 6px;
        margin: 7px 10px 7px 0;
        background-color: {{$baseColor}};
    }

    .list-item .list-sub-item {
        padding-top: 10px;
        padding-left: 20px;
    }

    .list-item .list-sub-item .list-sub-item-title:before {
        content: "";
        display: inline-block;
        height: 4px;
        width: 4px;
        margin: 7px 5px 7px 0;
        background-color: #315EFD;
    }

    .list-item .list-sub-item .list-sub-item-title h2 {
        display: inline-block;
        font: 400 15px/18px{{ $fontFamily }};
        margin: 0;
    }

    .new-page {
        display: block;
        break-before: page !important;
    }
</style>

<body>
<div class="document-borders"
     style="position: fixed; display: block; top: -47px; left: -45px; right: -45px;">
    @foreach($borderColors as $borderColor)
        <div class="document-border" style="
            display: block;
            position: absolute;
            background-color: {{ $borderColor }};
            height: 15px;
            width: {{ (100 / count($borderColors)) . '%' }};
            left: {{ (100 / count($borderColors)) * $loop->index . '%'}};};
            "></div>
    @endforeach
</div>

<!-- Header -->
<header>
    <div class="header-content">
        <div class="header-content-logo">
            @if (file_exists(storage_path("app/pre-check/logo-$implementation_key.png")))
                <img src="{{ storage_path("app/pre-check/logo-$implementation_key.png") }}"
                     alt="organization-logo"/>
            @else
                <img src="{{ storage_path('app/pre-check/logo-general.png') }}"
                     alt="organization-logo"/>
            @endif
        </div>

        <div class="header-content-separator">
            <div class="header-content-separator-line"></div>
        </div>

        <div class="header-content-date">
            <div class="header-content-date-title">Regelcheck</div>
            <div class="header-content-date-description">{{ $date_locale }}</div>
        </div>

    </div>

    <div class="header-separators">
        <div class="header-separator"></div>
        <div class="header-separator"></div>
    </div>
</header>

<footer>
    <div class="footer-content">
        <div class="footer-content-logo">
            @if (file_exists(storage_path("app/pre-check/logo-$implementation_key.png")))
                <img src="{{ storage_path("app/pre-check/logo-$implementation_key.png") }}"
                     alt="organization-logo"/>
            @else
                <img src="{{ storage_path('app/pre-check/logo-general.png') }}"
                     alt="organization-logo"/>
            @endif
        </div>

        <div class="footer-content-separator">
            <div class="footer-content-separator-line"></div>
        </div>

        <div class="footer-content-date">{{ $date_locale }}</div>
        <div class="footer-content-page"></div>
    </div>
</footer>

<main>
    <div style="min-height: 98%">
        <!-- Info block -->
        <h1>Welke regelingen kan ik aanvragen?</h1>

        <p>
            Er bestaan veel verschillende regelingen in de gemeente Westerkwartier.
            Op de website heeft u een paar vragen ingevuld om te zien voor welke regelingen u mogelijk in aanmerking komt.
            Op basis van de antwoorden die u heeft gegeven, ontvangt u een schatting per regeling.
        </p>

        <!-- Funds pre-check table -->
        <table>
            @if (count($funds) > 0)
                <tr>
                    <th>Regelingen</th>
                    <th>Kans</th>
                    <th>Uitleg</th>
                </tr>
            @endif

            @foreach($funds as $fund)
                <tr>
                    <td>
                        <strong>{{ $fund['name'] }} </strong>
                    </td>
                    <td>
                        @if ($fund['is_valid'])
                            Goede kans
                        @else
                            Gemiddelde of lage kans
                        @endif     
                    </td>
                    <td>
                        @if ($fund['is_valid'])
                            De regeling kunt u aanvragen

                            @if ($fund['is_external'] && $fund['external_link_url'])
                                via
                                <a href="{{ $fund['external_link_url'] }}">{{ $fund['external_link_text'] ?: 'Externe website bekijken' }}</a>
                            @endif

                        @else
                            U komt waarschijnlijk niet in aanmerking voor deze regeling.

                            @if ($fund['is_external'] && $fund['external_link_url'])
                                Meer weten? Klik op de link:
                                <a href="{{ $fund['external_link_url'] }}">{{ $fund['external_link_text'] ?: 'Externe website bekijken' }}</a>
                            @endif
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>

        <!-- Notice block -->
        <div class="notice-block">
            Let op: bovenstaande resultaat is een schatting. 
            U kunt altijd een aanvraag doen voor een regeling, ook als u een lage kans heeft. In sommige situaties is maatwerk mogelijk.
        </div>

        <!-- Instructions blocks -->
        <h1>Hoe nu verder?</h1>

        <div class="list-item">
            <div class="list-item-title">
                <h2>Regelingen aanvragen</h2>
            </div>

            <div class="list-item-description">
                Hierboven ziet u wat uw kans is om een in aanmerking te komen voor een regeling. 
                In de omschrijving ziet u waar u de regeling kunt aanvragen. Een regeling kunt u altijd aanvragen, ook als u een lage kans heeft.
            </div>
        </div>

        <div class="list-item">
            <div class="list-item-title">
                <h2>Heeft u vragen of hulp nodig?</h2>
            </div>

            <div class="list-item-description">
                Voor vragen of ondersteuning kunt u contact opnemen met de gemeente of een hulp punt.
                Er zijn verschillende organisaties die u graag helpen. Op de website vindt u meer informatie.
            </div>
        </div>
        
        <h1>Toelichting</h1>

        <p>
            Aan de informatie en resultaten kunt u geen rechten ontlenen. Wij streven ernaar om correcte en actuele informatie te bieden.
        </p>
    </div>
</main>

<div class="document-borders"
     style="position: fixed; display: block; bottom: -32px; left: -45px; right: -45px; background: red;">
    @foreach($borderColors as $borderColor)
        <div class="document-border" style="
                display: block;
                position: absolute;
                background-color: {{ $borderColor }};
                height: 15px;
                width: {{ (100 / count($borderColors)) . '%' }};
                left: {{ (100 / count($borderColors)) * $loop->index . '%'}};};
                "></div>
    @endforeach
</div>
</body>
</html>
