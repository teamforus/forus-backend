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
        header {
            position: fixed;
            top: -50px;
            left: 0;
            right: 0;
            padding: 50px 30px;
            margin-bottom: 20px;
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
                    font: 700 15px/18px Montserrat;
                }

                td {
                    padding: 10px;
                    font: 400 15px/18px Montserrat;
                    border-bottom: 1px solid #000;
                }
            }
        }

        .pagenum:before {
            content: "Pagina " counter(page);
            font: 600 12px/18px Montserrat;
        }
    </style>

    <body style="padding: 30px 30px 30px 30px; margin: 70px 0 40px;">
        <!-- Header -->
        <header>
            <div style="margin-bottom: 20px;">
                <div style="display: inline-block; width: 200px; margin-right: 20px;">
                    @if (file_exists(public_path("assets/pre-check-totals-export/logo-$implementation_key.png")))
                        <img src="{{ public_path('assets/pre-check-totals-export/logo-' . $implementation_key . '.png') }}" style="width: 100%;" alt="organization-logo"/>
                    @else
                        <img src="{{ public_path('assets/pre-check-totals-export/logo-general.png') }}" style="width: 100%;" alt="organization-logo"/>
                    @endif
                </div>

                <div style="display: inline-block; margin-left: 20px;">
                    <div style="font: 800 16px/22px Montserrat;">Forus PreCheck</div>
                    <div style="font: 500 12px/18px Montserrat;">{{ $date_locale }}</div>
                </div>

                <div style="float: right; text-align: center;">
                    <div style="font: 500 12px/12px Montserrat; letter-spacing: 5px; margin-bottom: 5px;">POWERED BY</div>
                    <img src="{{ public_path('assets/pre-check-totals-export/logo-forus.png') }}" style="max-width: 80px;" alt="forus-logo"/>
                </div>
            </div>

            <div style="display: inline-block; width: 49%; border-bottom: 4px solid #000; border-radius: 12px; margin-right: 0.5%;"></div>
            <div style="display: inline-block; width: 49%; border-bottom: 4px solid #CDCED2; border-radius: 12px;"></div>
        </header>

        <footer>
            <div style="display: inline-block; width: 100px; padding-right: 30px; border-right: 1px solid #9E9E9E; margin-right: 30px;">
                <img src="{{ public_path('assets/pre-check-totals-export/footer-' . ($implementation_key == 'general' ? 'blue' : 'green') . '.png') }}" style="width: 100%;" alt="organization-logo"/>
            </div>

            <div style="display: inline-block;">
                <div style="font: 600 12px/18px Montserrat;">{{ $date_locale }}</div>
            </div>

            <div style="float: right;">
                <span class="pagenum"></span>
            </div>
        </footer>

        <main>
            <!-- Info block -->
            <h1 style="font: 800 20px/24px Montserrat;">Op welke tegemoetkomingen heeft u recht?</h1>

            <h2 style="font: 400 15px/18px Montserrat; margin-bottom: 20px;">
                Er bestaan allerlei tegemoetkomingen. Soms meer dan u denkt.
                Bereken in een paar stappen op wel kebedragen u mogelijk recht heeft.
                Ook ziet u waar u deze kunt aanvragen.
            </h2>

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
            <div style="background-color: #F4F5F7; padding: 20px; font: 400 15px/18px Montserrat; margin-bottom: 30px;">
                Er bestaan allerlei tegemoetkomingen. Soms meer dan u denkt.
                Bereken in een paar stappen op wel kebedragen u mogelijk recht heeft. Ook ziet u waar u deze kunt aanvragen.
            </div>

            <!-- Instructions blocks -->
            <h1 style="font: 800 20px/24px Montserrat;">Hoe nu verder?</h1>

            <div style="font: 400 15px/18px Montserrat; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #9E9E9E;">
                <div style="margin-bottom: 10px;">
                    <div style="display: inline-block; background-color: #315EFD; height: 6px; width: 5px; margin: 7px 10px 7px 0;"></div>
                    <h2 style="display: inline-block; font: 700 16px/20px Montserrat; margin: 0;">Vraag toeslagen direct aan</h2>
                </div>

                Let op dat u uw inkomen niet te laag inschat: u ontvangt dan namelijk een hoger bedrag dan waar u uiteindelijk recht op heeft.
                Dit moet u later terugbetalen.
                De toeslagen van de Belastingdienst die u krijgt zijn namelijk voorlopige bedragen. De definitieve berekening ontvangt u 9 tot 12 maanden na afloop van het kalenderjaar.
            </div>

            <div style="font: 400 15px/18px Montserrat; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #9E9E9E;">
                <div style="margin-bottom: 10px;">
                    <div style="display: inline-block; background-color: #315EFD; height: 6px; width: 5px; margin: 7px 10px 7px 0;"></div>
                    <h2 style="display: inline-block; font: 700 16px/20px Montserrat; margin: 0;">Geef veranderingen in uw situatie op tijd door</h2>
                </div>

                Worden uw inkomsten hoger? Of verandert uw gezinssituatie of uw woonsituatie?
                Dit kan invloed hebben op de hoogte van uw toeslagen. Geef daarom alle veranderingen meteen door via www.toeslagen.nl.
                U kunt daar instellen vanaf wanneer de nieuwe situatie in gaat.
            </div>

            <div style="font: 400 15px/18px Montserrat; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #9E9E9E;">
                <div style="margin-bottom: 10px;">
                    <div style="display: inline-block; background-color: #315EFD; height: 6px; width: 5px; margin: 7px 10px 7px 0;"></div>
                    <h2 style="display: inline-block; font: 700 16px/20px Montserrat; margin: 0;">Check of u recht heeft op bijdragen vanuit uw gemeente</h2>
                </div>

                Afhankelijk van het inkomen en uw gezinssituatie kunt u in aanmerking komen voor tegemoetkomingen die uw gemeente aanbiedt.
                Deze regelingen verschillen per gemeente. Denk aan een:

                <div style="padding-top: 10px; padding-left: 20px;">
                    <div>
                        <div style="display: inline-block; background-color: #315EFD; height: 4px; width: 4px; margin: 7px 5px 7px 0;"></div>
                        <h2 style="display: inline-block; font: 400 15px/18px Montserrat; margin: 0;">Korting op een zorgverzekering</h2>
                    </div>

                    <div>
                        <div style="display: inline-block; background-color: #315EFD; height: 4px; width: 4px; margin: 7px 5px 7px 0;"></div>
                        <h2 style="display: inline-block; font: 400 15px/18px Montserrat; margin: 0;">Bijdrage voor sport- en culturele activiteiten</h2>
                    </div>
                </div>
            </div>

            <div>
                <h1 style="font: 800 20px/24px Montserrat;">Disclaimer</h1>
                <div>
                    BerekenUwRecht is met de grootst mogelijke zorgvuldigheid samengesteld.
                    Het Nibud streeft er vanzelfsprekend naar om altijd correcte en actuele informatie te bieden.
                    Aan de verstrekte informatie kunnen geen rechten worden ontleend.
                    Het Nibud aanvaardt geen enkele aansprakelijkheid voor de inhoud van BerekenUwRecht en de daarin verstrekte informatie.
                    is met de grootst mogelijke zorgvuldigheid samengesteld.
                    Het Nibud streeft er vanzelfsprekend naar om altijd correcte en actuele informatie te bieden.
                    Aan de verstrekte informatie kunnen geen rechten worden ontleend.
                    Het Nibud aanvaardt geen enkele aansprakelijkheid voor de inhoud van de BerekenUwRecht de daarin verstrekte informatie.
                </div>
            </div>
        </main>
    </body>
</html>