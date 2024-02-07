<?php
/** @var array $funds */
?>

<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>

    <style>
        body {
            padding: 30px;
            margin: 70px 0 40px;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 0 30px;
            margin-bottom: 10px;
        }

        header .border-left {
            display: inline-block;
            width: 49%;
            margin-right: 0.5%;
            border-bottom: 4px solid #000;
            border-radius: 12px;
        }

        header .border-right {
            display: inline-block;
            width: 49%;
            border-bottom: 4px solid #CDCED2;
            border-radius: 12px;
        }

        .document-border-top {
            position: fixed;
            top: -45px;
            left: -50px;
            right: -50px;
            width: 100vw;
            height: 6px;
            background-color: #80B3CD;
        }

        .document-border-top:before, .document-border-bottom:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 33%;
            height: 6px;
            background-color: #01689B;
        }

        .document-border-top:after, .document-border-bottom:after {
            --document-border-left-color: #01689B;
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 33%;
            height: 6px;
            background-color: #A6CADC;
        }

        .document-border-bottom {
            position: fixed;
            bottom: -45px;
            left: -50px;
            right: -50px;
            width: 100vw;
            height: 6px;
            background-color: #80B3CD;
        }

        footer {
            position: fixed;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 50px;
            padding: 30px;
            margin-top: 300px;
        }

        footer .logo-block {
            display: inline-block;
            width: 100px;
            padding-right: 30px;
            border-right: 1px solid #9E9E9E;
            margin-right: 30px;
        }

        footer .logo-block img {
            width: 100%;
        }

        footer .footer-date {
            display: inline-block;
            font: 600 12px/18px Arial, sans-serif, Helvetica;
        }

        h1 {
            margin: 0 0 20px 0;
            font: 800 20px/24px Arial, sans-serif, Helvetica;
        }

        p {
            font: 400 15px/18px Arial, sans-serif, Helvetica;
            margin-bottom: 20px;
        }

        table {
            border-spacing: 3px;
            margin-bottom: 10px;

            tr {
                th, td {
                    text-align: left;
                }

                th {
                    text-align: left;
                    white-space: nowrap;
                    padding: 10px;
                    background-color: #F4F5F7;
                    border-bottom: 2px solid #315EFD;
                    font: 700 15px/18px Arial, sans-serif, Helvetica;
                }

                td {
                    padding: 10px;
                    font: 400 15px/18px Arial, sans-serif, Helvetica;
                    border-bottom: 1px solid #000;
                }
            }
        }

        .implementation-logo {
            display: inline-block;
            width: 200px;
            margin-right: 20px;
        }

        .implementation-logo img {
            width: 100%;
        }

        .header-wrapper {
            margin-bottom: 15px;
        }

        .header-wrapper .date-block {
            display: inline-block;
            margin-left: 20px;
        }

        .header-wrapper .date-block .title {
            font: 800 16px/22px Arial, sans-serif, Helvetica;
        }

        .header-wrapper .date-block .description {
            font: 500 12px/18px Arial, sans-serif, Helvetica;
        }

        .header-wrapper .powered-by-block {
            float: right;
            text-align: center;
        }

        .header-wrapper .powered-by-block .description {
            font: 500 12px/12px Arial, sans-serif, Helvetica;
            letter-spacing: 5px;
            margin-bottom: 5px;
        }

        .header-wrapper .powered-by-block img {
            max-width: 80px;
        }

        .notice-block {
            background-color: #F4F5F7;
            padding: 20px;
            font: 400 15px/18px Arial, sans-serif, Helvetica;
            margin-bottom: 30px;
        }

        .list-item {
            font: 400 15px/18px Arial, sans-serif, Helvetica;
            margin-bottom: 20px;
            padding-bottom: 20px;
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
            font: 700 16px/20px Arial, sans-serif, Helvetica;
            margin: 0;
        }

        .list-item .list-item-title:before {
            content: "";
            display: inline-block;
            height: 6px;
            width: 5px;
            margin: 7px 10px 7px 0;
            background-color: #315EFD;
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
            font: 400 15px/18px Arial, sans-serif, Helvetica;
            margin: 0;
        }

        .pagenum {
            float: right;
            display: inline-block;
        }

        .pagenum:before {
            content: "Pagina " counter(page);
            font: 600 12px/18px Arial, sans-serif, Helvetica;
        }
    </style>

    <body>
        <div class="document-border-top"></div>
        <div class="document-border-bottom"></div>

        <!-- Header -->
        <header>
            <div class="header-wrapper">
                <div class="implementation-logo">
                    @if (file_exists(storage_path("app/pre-check/logo-$implementation_key.png")))
                        <img src="{{ storage_path("app/pre-check/logo-$implementation_key.png") }}" alt="organization-logo"/>
                    @else
                        <img src="{{ storage_path('app/pre-check/logo-general.png') }}" alt="organization-logo"/>
                    @endif
                </div>

                <div class="date-block">
                    <div class="title">Forus PreCheck</div>
                    <div class="description">{{ $date_locale }}</div>
                </div>

                <div class="powered-by-block">
                    <div class="description">POWERED BY</div>
                    <img src="{{ storage_path('app/pre-check/logo-forus.png') }}" alt="forus-logo"/>
                </div>
            </div>

            <div class="border-left"></div>
            <div class="border-right"></div>
        </header>

        <footer>
            <div class="logo-block">
                <img src="{{ storage_path('app/pre-check/footer-blue.png') }}" alt="organization-logo"/>
            </div>

            <div class="footer-date">{{ $date_locale }}</div>

            <div class="pagenum"></div>
        </footer>

        <main>
            <!-- Info block -->
            <h1>Op welke tegemoetkomingen heeft u recht?</h1>

            <p>
                Er bestaan allerlei tegemoetkomingen. Soms meer dan u denkt.
                Bereken in een paar stappen op wel kebedragen u mogelijk recht heeft.
                Ook ziet u waar u deze kunt aanvragen.
            </p>

            <!-- Funds pre-check table -->
            <table>
                @if (count($funds) > 0)
                    <tr>
                        <th>Uw tegemoetkomingen</th>
                        <th>Indicatie bedrag per maand</th>
                        <th>Uw tegemoetkomingen</th>
                    </tr>
                @endif

                @foreach($funds as $fund)
                    <tr>
                        <td>
                            <strong>{{ $fund['name'] }} </strong>
                        </td>
                        <td>
                            <strong>{{ $fund['amount_total_locale'] }}</strong>
                        </td>
                        <td>
                            @if ($fund['is_valid'])
                                Ja, de zorgtoeslag moet u zelf aanvragen

                                @if ($fund['is_external'] && $fund['external_link_url'])
                                    via <a href="{{ $fund['external_link_url'] }}">{{ $fund['external_link_text'] ?: 'Externe website bekijken' }}</a>
                                @endif

                            @else
                                U heeft op basis van uw gegeven antwoorden geen recht op toeslagen.

                                @if ($fund['is_external'] && $fund['external_link_url'])
                                    Op <a href="{{ $fund['external_link_url'] }}">{{ $fund['external_link_text'] ?: 'Externe website bekijken' }}</a>
                                    kunt u zien wat de voorwaarden zijn om in aanmerking te komen voor de huurtoeslag.
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>

            <!-- Notice block -->
            <div class="notice-block">
                Er bestaan allerlei tegemoetkomingen. Soms meer dan u denkt.
                Bereken in een paar stappen op wel kebedragen u mogelijk recht heeft. Ook ziet u waar u deze kunt aanvragen.
            </div>

            <!-- Instructions blocks -->
            <h1>Hoe nu verder?</h1>

            <div class="list-item">
                <div class="list-item-title">
                    <h2>Vraag toeslagen direct aan</h2>
                </div>

                <div class="list-item-description">
                    Let op dat u uw inkomen niet te laag inschat: u ontvangt dan namelijk een hoger bedrag dan waar u uiteindelijk recht op heeft.
                    Dit moet u later terugbetalen.
                    De toeslagen van de Belastingdienst die u krijgt zijn namelijk voorlopige bedragen.
                    De definitieve berekening ontvangt u 9 tot 12 maanden na afloop van het kalenderjaar.
                </div>
            </div>

            <div class="list-item">
                <div class="list-item-title">
                    <h2>Geef veranderingen in uw situatie op tijd door</h2>
                </div>

                <div class="list-item-description">
                    Worden uw inkomsten hoger? Of verandert uw gezinssituatie of uw woonsituatie?
                    Dit kan invloed hebben op de hoogte van uw toeslagen. Geef daarom alle veranderingen meteen door via www.toeslagen.nl.
                    U kunt daar instellen vanaf wanneer de nieuwe situatie in gaat.
                </div>
            </div>

            <div class="list-item">
                <div class="list-item-title">
                    <h2>Check of u recht heeft op bijdragen vanuit uw gemeente</h2>
                </div>

                <div class="list-item-description">
                    Afhankelijk van het inkomen en uw gezinssituatie kunt u in aanmerking komen voor tegemoetkomingen die uw gemeente aanbiedt.
                    Deze regelingen verschillen per gemeente. Denk aan een:
                </div>

                <div class="list-sub-item">
                    <div class="list-sub-item-title">
                        <h2>Korting op een zorgverzekering</h2>
                    </div>

                    <div class="list-sub-item-title">
                        <h2>Bijdrage voor sport- en culturele activiteiten</h2>
                    </div>
                </div>
            </div>

            <h1>Disclaimer</h1>

            <p>
                BerekenUwRecht is met de grootst mogelijke zorgvuldigheid samengesteld.
                Het Nibud streeft er vanzelfsprekend naar om altijd correcte en actuele informatie te bieden.
                Aan de verstrekte informatie kunnen geen rechten worden ontleend.
                Het Nibud aanvaardt geen enkele aansprakelijkheid voor de inhoud van BerekenUwRecht en de daarin verstrekte informatie.
                is met de grootst mogelijke zorgvuldigheid samengesteld.
                Het Nibud streeft er vanzelfsprekend naar om altijd correcte en actuele informatie te bieden.
                Aan de verstrekte informatie kunnen geen rechten worden ontleend.
                Het Nibud aanvaardt geen enkele aansprakelijkheid voor de inhoud van de BerekenUwRecht de daarin verstrekte informatie.
            </p>
        </main>
    </body>
</html>