<?php

return [
    'blocks' => [
        'info' => [
            'name' => 'Info',
            'fields' => [
                'section_title' => [
                    'name' => 'Sectietitel',
                    'hint' => 'Max. 100 tekens',
                    'placeholder' => 'Titel...',
                ],
                'section_description' => [
                    'name' => 'Sectieomschrijving',
                    'hint' => 'Max. 10000 tekens',
                    'placeholder' => 'Omschrijving...',
                ],
                'section_background_color' => [
                    'name' => 'Achtergrondkleur van sectie',
                    'placeholder' => '#ffffff',
                ],
                'section_spacing' => [
                    'name' => 'Ruimte boven/onder',
                    'options' => [
                        'default' => [
                            'name' => 'Standaard',
                        ],
                        'none' => [
                            'name' => 'Geen ruimte boven/onder',
                        ],
                        'no_top' => [
                            'name' => 'Geen ruimte boven',
                        ],
                        'no_bottom' => [
                            'name' => 'Geen ruimte onder',
                        ],
                    ],
                ],
                'blocks_per_row' => [
                    'name' => 'Blokken per rij',
                    'options' => [
                        'columns_1' => [
                            'name' => '1 kolom',
                        ],
                        'columns_2' => [
                            'name' => '2 kolommen',
                        ],
                        'columns_3' => [
                            'name' => '3 kolommen',
                        ],
                    ],
                ],
            ],
            'items' => [
                'post' => [
                    'name' => 'Bericht',
                    'fields' => [
                        'media' => [
                            'name' => 'Afbeelding',
                        ],
                        'label' => [
                            'name' => 'Label',
                            'hint' => 'Max. 30 tekens',
                            'placeholder' => 'Label...',
                        ],
                        'title' => [
                            'name' => 'Titel',
                            'hint' => 'Max. 100 tekens',
                            'placeholder' => 'Titel...',
                        ],
                        'description' => [
                            'name' => 'Omschrijving',
                            'hint' => 'Max. 500 tekens',
                            'placeholder' => 'Omschrijving...',
                        ],
                        'button_enabled' => [
                            'name' => 'Knop',
                        ],
                        'button_text' => [
                            'name' => 'Knoptekst',
                            'placeholder' => 'Knoptekst',
                        ],
                        'button_link' => [
                            'name' => 'Knoplink',
                            'placeholder' => 'https://voorbeeld.nl',
                        ],
                        'button_link_label' => [
                            'name' => 'Label voor knoplink',
                            'hint' => 'Beschrijf waar de knop naartoe gaat voor gebruikers van een schermlezer. ' .
                                'Dit mag afwijken van de zichtbare knoptekst.',
                            'placeholder' => 'Label voor knoplink',
                        ],
                        'button_target_blank' => [
                            'name' => 'Knoplink openen in',
                            'options' => [
                                'same_tab' => [
                                    'name' => 'Hetzelfde tabblad',
                                ],
                                'new_tab' => [
                                    'name' => 'Nieuw tabblad',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'text' => [
            'name' => 'Tekst',
            'fields' => [
                'section_title' => [
                    'name' => 'Sectietitel',
                    'hint' => 'Max. 100 tekens',
                    'placeholder' => 'Titel...',
                ],
                'section_description' => [
                    'name' => 'Sectieomschrijving',
                    'hint' => 'Max. 10000 tekens',
                    'placeholder' => 'Omschrijving...',
                ],
                'section_background_color' => [
                    'name' => 'Achtergrondkleur van sectie',
                    'placeholder' => '#ffffff',
                ],
                'section_spacing' => [
                    'name' => 'Ruimte boven/onder',
                    'options' => [
                        'default' => [
                            'name' => 'Standaard',
                        ],
                        'none' => [
                            'name' => 'Geen ruimte boven/onder',
                        ],
                        'no_top' => [
                            'name' => 'Geen ruimte boven',
                        ],
                        'no_bottom' => [
                            'name' => 'Geen ruimte onder',
                        ],
                    ],
                ],
            ],
        ],
        'banner' => [
            'name' => 'Banner',
            'fields' => [
                'section_title' => [
                    'name' => 'Sectietitel',
                    'placeholder' => 'Titel...',
                ],
                'section_description' => [
                    'name' => 'Sectieomschrijving',
                    'placeholder' => 'Omschrijving...',
                ],
                'section_background_color' => [
                    'name' => 'Achtergrondkleur van sectie',
                    'placeholder' => '#ffffff',
                ],
                'section_spacing' => [
                    'name' => 'Ruimte boven/onder',
                    'options' => [
                        'default' => [
                            'name' => 'Standaard',
                        ],
                        'none' => [
                            'name' => 'Geen ruimte boven/onder',
                        ],
                        'no_top' => [
                            'name' => 'Geen ruimte boven',
                        ],
                        'no_bottom' => [
                            'name' => 'Geen ruimte onder',
                        ],
                    ],
                ],
                'image' => [
                    'name' => 'Afbeelding',
                ],
                'layout' => [
                    'name' => 'Indeling',
                    'preview_text' => 'Tekst',
                    'options' => [
                        'image_left' => [
                            'name' => 'Afbeelding links, tekst rechts',
                            'short_name' => 'Tekst rechts',
                        ],
                        'image_right' => [
                            'name' => 'Tekst links, afbeelding rechts',
                            'short_name' => 'Tekst links',
                        ],
                        'image_overlay_left' => [
                            'name' => 'Afbeelding met tekst links',
                            'short_name' => 'Afbeelding 1',
                        ],
                        'image_overlay_center' => [
                            'name' => 'Afbeelding met tekst in het midden',
                            'short_name' => 'Afbeelding 2',
                        ],
                        'image_overlay_right' => [
                            'name' => 'Afbeelding met tekst rechts',
                            'short_name' => 'Afbeelding 3',
                        ],
                    ],
                ],
                'text_background_color' => [
                    'name' => 'Achtergrondkleur tekstvlak / afbeeldingsoverlay',
                    'hint' => 'Bij een indeling met tekst over de afbeelding wordt deze kleur als overlay gebruikt. ' .
                        'Gebruik transparantie om de afbeelding zichtbaar te houden.',
                    'placeholder' => '#ffffff',
                ],
                'text_color' => [
                    'name' => 'Tekstkleur',
                    'placeholder' => '#4E4D40',
                ],
                'url' => [
                    'name' => 'Link',
                    'hint' => 'Zonder knop geldt deze link voor de hele banner. Met een knop bepaalt ' .
                        '‘Link toepassen op’ of de hele banner of alleen de knop klikbaar is.',
                    'placeholder' => 'https://voorbeeld.nl',
                ],
                'link_label' => [
                    'name' => 'Toegankelijke linktekst',
                    'hint' => 'Beschrijf waar de bannerlink naartoe gaat voor gebruikers van een schermlezer.',
                    'placeholder' => 'Bekijk de actie',
                ],
                'target_blank' => [
                    'name' => 'Link openen in',
                    'options' => [
                        'same_tab' => [
                            'name' => 'Hetzelfde tabblad',
                        ],
                        'new_tab' => [
                            'name' => 'Nieuw tabblad',
                        ],
                    ],
                ],
                'label_enabled' => [
                    'name' => 'Label tonen',
                ],
                'label' => [
                    'name' => 'Label',
                    'placeholder' => 'Label...',
                ],
                'label_background_color' => [
                    'name' => 'Achtergrondkleur van label',
                    'placeholder' => '#ffffff',
                ],
                'label_text_color' => [
                    'name' => 'Tekstkleur van label',
                    'placeholder' => '#4E4D40',
                ],
                'button_enabled' => [
                    'name' => 'Knop',
                ],
                'link_area' => [
                    'name' => 'Link toepassen op',
                    'options' => [
                        'banner' => [
                            'name' => 'Hele banner',
                        ],
                        'button' => [
                            'name' => 'Alleen knop',
                        ],
                    ],
                ],
                'button_label' => [
                    'name' => 'Knoptekst',
                    'placeholder' => 'Knoptekst',
                ],
                'button_color' => [
                    'name' => 'Knopkleur',
                    'placeholder' => '#4E4D40',
                ],
                'button_text_color' => [
                    'name' => 'Tekstkleur van knop',
                    'placeholder' => '#ffffff',
                ],
            ],
        ],
        'callout' => [
            'name' => 'Aandachtsblok',
            'fields' => [
                'section_title' => [
                    'name' => 'Sectietitel',
                    'placeholder' => 'Titel...',
                ],
                'section_description' => [
                    'name' => 'Sectieomschrijving',
                    'placeholder' => 'Omschrijving...',
                ],
                'section_background_color' => [
                    'name' => 'Achtergrondkleur van sectie',
                    'placeholder' => '#ffffff',
                ],
                'section_spacing' => [
                    'name' => 'Ruimte boven/onder',
                    'options' => [
                        'default' => [
                            'name' => 'Standaard',
                        ],
                        'none' => [
                            'name' => 'Geen ruimte boven/onder',
                        ],
                        'no_top' => [
                            'name' => 'Geen ruimte boven',
                        ],
                        'no_bottom' => [
                            'name' => 'Geen ruimte onder',
                        ],
                    ],
                ],
                'image' => [
                    'name' => 'Afbeelding',
                ],
                'label' => [
                    'name' => 'Label',
                    'placeholder' => 'Label...',
                ],
                'button_enabled' => [
                    'name' => 'Knop',
                ],
                'button_text' => [
                    'name' => 'Knoptekst',
                    'placeholder' => 'Knoptekst',
                ],
                'button_link' => [
                    'name' => 'Knoplink',
                    'placeholder' => 'https://voorbeeld.nl',
                ],
                'button_target_blank' => [
                    'name' => 'Knoplink openen in',
                    'options' => [
                        'same_tab' => [
                            'name' => 'Hetzelfde tabblad',
                        ],
                        'new_tab' => [
                            'name' => 'Nieuw tabblad',
                        ],
                    ],
                ],
                'content_alignment' => [
                    'name' => 'Uitlijning',
                    'options' => [
                        'left' => [
                            'name' => 'Links',
                        ],
                        'center' => [
                            'name' => 'Midden',
                        ],
                        'right' => [
                            'name' => 'Rechts',
                        ],
                    ],
                ],
            ],
        ],
        'faq' => [
            'name' => 'Veelgestelde vragen',
            'fields' => [
                'section_title' => [
                    'name' => 'Sectietitel',
                    'placeholder' => 'Titel...',
                ],
                'section_description' => [
                    'name' => 'Sectieomschrijving',
                    'placeholder' => 'Omschrijving...',
                ],
                'section_background_color' => [
                    'name' => 'Achtergrondkleur van sectie',
                    'placeholder' => '#ffffff',
                ],
                'section_spacing' => [
                    'name' => 'Ruimte boven/onder',
                    'options' => [
                        'default' => [
                            'name' => 'Standaard',
                        ],
                        'none' => [
                            'name' => 'Geen ruimte boven/onder',
                        ],
                        'no_top' => [
                            'name' => 'Geen ruimte boven',
                        ],
                        'no_bottom' => [
                            'name' => 'Geen ruimte onder',
                        ],
                    ],
                ],
            ],
            'items' => [
                'item' => [
                    'name' => 'FAQ onderdeel',
                    'fields' => [
                        'type' => [
                            'name' => 'Type',
                            'hint' => 'Met ‘Titel’ start u een nieuwe groep. De volgende vragen horen bij die groep ' .
                                'tot de volgende titel. Een groep zonder vragen wordt niet getoond.',
                            'options' => [
                                'question' => [
                                    'name' => 'Vraag',
                                ],
                                'title' => [
                                    'name' => 'Titel',
                                ],
                            ],
                        ],
                        'title' => [
                            'name' => 'Titel',
                            'placeholder' => 'Titel...',
                        ],
                        'subtitle' => [
                            'name' => 'Subtitel',
                            'placeholder' => 'Subtitel...',
                        ],
                        'description' => [
                            'name' => 'Antwoord',
                            'placeholder' => 'Antwoord...',
                        ],
                    ],
                ],
            ],
        ],
        'link_panels' => [
            'name' => 'Linkpanelen',
            'fields' => [
                'section_title' => [
                    'name' => 'Sectietitel',
                    'placeholder' => 'Titel...',
                ],
                'section_description' => [
                    'name' => 'Sectieomschrijving',
                    'placeholder' => 'Omschrijving...',
                ],
                'section_background_color' => [
                    'name' => 'Achtergrondkleur van sectie',
                    'placeholder' => '#ffffff',
                ],
                'section_spacing' => [
                    'name' => 'Ruimte boven/onder',
                    'options' => [
                        'default' => [
                            'name' => 'Standaard',
                        ],
                        'none' => [
                            'name' => 'Geen ruimte boven/onder',
                        ],
                        'no_top' => [
                            'name' => 'Geen ruimte boven',
                        ],
                        'no_bottom' => [
                            'name' => 'Geen ruimte onder',
                        ],
                    ],
                ],
                'columns' => [
                    'name' => 'Aantal kolommen',
                    'options' => [
                        'columns_1' => [
                            'name' => '1 kolom',
                        ],
                        'columns_2' => [
                            'name' => '2 kolommen',
                        ],
                        'columns_3' => [
                            'name' => '3 kolommen',
                        ],
                    ],
                ],
            ],
            'items' => [
                'panel' => [
                    'name' => 'Paneel',
                    'fields' => [
                        'title' => [
                            'name' => 'Titel',
                            'placeholder' => 'Titel...',
                        ],
                        'description' => [
                            'name' => 'Omschrijving',
                            'placeholder' => 'Omschrijving...',
                        ],
                        'links' => [
                            'name' => 'Links',
                            'placeholder' => 'Voeg links toe als lijst...',
                        ],
                        'button_text' => [
                            'name' => 'Linktekst',
                            'placeholder' => 'Linktekst...',
                        ],
                        'button_link' => [
                            'name' => 'Link',
                            'placeholder' => 'https://voorbeeld.nl',
                        ],
                        'button_target_blank' => [
                            'name' => 'Link openen in',
                            'options' => [
                                'same_tab' => [
                                    'name' => 'Hetzelfde tabblad',
                                ],
                                'new_tab' => [
                                    'name' => 'Nieuw tabblad',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'providers_map' => [
            'name' => 'Aanbiederskaart',
            'fields' => [
                'section_title' => [
                    'name' => 'Sectietitel',
                    'placeholder' => 'Bekijk onze kaart',
                ],
                'section_description' => [
                    'name' => 'Sectieomschrijving',
                    'placeholder' => 'Vind locaties en voorzieningen bij u in de buurt.',
                ],
                'section_background_color' => [
                    'name' => 'Achtergrondkleur van sectie',
                    'placeholder' => '#ffffff',
                ],
                'section_spacing' => [
                    'name' => 'Ruimte boven/onder',
                    'options' => [
                        'default' => [
                            'name' => 'Standaard',
                        ],
                        'none' => [
                            'name' => 'Geen ruimte boven/onder',
                        ],
                        'no_top' => [
                            'name' => 'Geen ruimte boven',
                        ],
                        'no_bottom' => [
                            'name' => 'Geen ruimte onder',
                        ],
                    ],
                ],
                'button_text' => [
                    'name' => 'Knoptekst',
                    'placeholder' => 'Toon kaart',
                ],
            ],
        ],
        'provider_signup' => [
            'name' => 'Aanmelden als aanbieder',
            'fields' => [
                'section_title' => [
                    'name' => 'Sectietitel',
                    'placeholder' => 'Aanmelden als aanbieder',
                    'default' => 'Aanmelden als aanbieder',
                ],
                'section_description' => [
                    'name' => 'Sectieomschrijving',
                    'placeholder' => 'Omschrijving...',
                    'default' => 'Door het online formulier in te vullen meldt u uw organisatie aan als aanbieder. ' .
                        "Het invullen duurt ongeveer 15 minuten.\n\nLees de instructie in elke stap goed door.",
                ],
                'section_background_color' => [
                    'name' => 'Achtergrondkleur van sectie',
                    'placeholder' => '#ffffff',
                ],
                'section_spacing' => [
                    'name' => 'Ruimte boven/onder',
                    'options' => [
                        'default' => [
                            'name' => 'Standaard',
                        ],
                        'none' => [
                            'name' => 'Geen ruimte boven/onder',
                        ],
                        'no_top' => [
                            'name' => 'Geen ruimte boven',
                        ],
                        'no_bottom' => [
                            'name' => 'Geen ruimte onder',
                        ],
                    ],
                ],
                'image' => [
                    'name' => 'Afbeelding',
                    'hint' => 'Als u geen afbeelding kiest, wordt de standaardillustratie voor aanmelden als ' .
                        'aanbieder getoond.',
                ],
                'button_text' => [
                    'name' => 'Knoptekst',
                    'placeholder' => 'Aanmelden',
                    'default' => 'Aanmelden',
                ],
                'login_enabled' => [
                    'name' => 'Loginlink tonen',
                ],
                'login_text' => [
                    'name' => 'Logintekst',
                    'placeholder' => 'Heeft u al een account?',
                    'default' => 'Heeft u al een account?',
                ],
                'login_link_text' => [
                    'name' => 'Loginlink tekst',
                    'placeholder' => 'Log dan in',
                    'default' => 'Log dan in',
                ],
            ],
        ],
        'product_categories' => [
            'name' => 'Aanbod categorieën',
            'fields' => [
                'section_title' => [
                    'name' => 'Sectietitel',
                    'placeholder' => 'Wat lijkt jou leuk om te doen?',
                ],
                'section_description' => [
                    'name' => 'Sectieomschrijving',
                    'placeholder' => 'Klik op een thema en je ziet meteen uit welk aanbod je kan kiezen.',
                ],
                'section_spacing' => [
                    'name' => 'Ruimte boven/onder',
                    'options' => [
                        'default' => [
                            'name' => 'Standaard',
                        ],
                        'none' => [
                            'name' => 'Geen ruimte boven/onder',
                        ],
                        'no_top' => [
                            'name' => 'Geen ruimte boven',
                        ],
                        'no_bottom' => [
                            'name' => 'Geen ruimte onder',
                        ],
                    ],
                ],
                'section_background_type' => [
                    'name' => 'Achtergrondtype van sectie',
                    'options' => [
                        'shape' => [
                            'name' => 'Decoratieve vorm',
                        ],
                        'solid' => [
                            'name' => 'Effen kleur',
                        ],
                    ],
                ],
                'section_background_color' => [
                    'name' => 'Achtergrondkleur van sectie',
                    'placeholder' => '#ffffff',
                ],
                'section_background_shape_color' => [
                    'name' => 'Kleur van decoratieve vorm',
                    'placeholder' => '#315EFD',
                ],
            ],
        ],
        'product_showcase' => [
            'name' => 'Aanbod',
            'fields' => [
                'section_title' => [
                    'name' => 'Sectietitel',
                    'placeholder' => 'Aanbod',
                    'default' => 'Aanbod',
                ],
                'section_description' => [
                    'name' => 'Sectieomschrijving',
                    'placeholder' => 'Omschrijving...',
                ],
                'section_background_color' => [
                    'name' => 'Achtergrondkleur van sectie',
                    'placeholder' => '#ffffff',
                ],
                'section_spacing' => [
                    'name' => 'Ruimte boven/onder',
                    'options' => [
                        'default' => [
                            'name' => 'Standaard',
                        ],
                        'none' => [
                            'name' => 'Geen ruimte boven/onder',
                        ],
                        'no_top' => [
                            'name' => 'Geen ruimte boven',
                        ],
                        'no_bottom' => [
                            'name' => 'Geen ruimte onder',
                        ],
                    ],
                ],
                'product_count' => [
                    'name' => 'Aantal producten',
                    'hint' => 'Bepaalt hoeveel willekeurig geselecteerde, beschikbare producten worden getoond.',
                    'options' => [
                        '3' => [
                            'name' => '3 producten',
                        ],
                        '6' => [
                            'name' => '6 producten',
                        ],
                        '9' => [
                            'name' => '9 producten',
                        ],
                        '12' => [
                            'name' => '12 producten',
                        ],
                    ],
                ],
                'button_text' => [
                    'name' => 'Knoptekst',
                    'placeholder' => 'Bekijk meer',
                    'default' => 'Bekijk meer',
                ],
            ],
        ],
    ],
    'validation' => [
        'attributes' => [
            'cms_blocks' => 'CMS-blokken',
            'cms_block' => 'CMS-blok',
            'block_type_key' => 'bloktype',
            'state' => 'status',
            'block_values' => 'blokvelden',
            'block_items' => 'blokitems',
            'block_item' => 'blokitem',
            'item_type_key' => 'itemtype',
            'item_values' => 'itemvelden',
            'image' => 'afbeelding',
            'layout' => 'indeling',
            'section_title' => 'sectietitel',
            'section_description' => 'sectieomschrijving',
            'section_background_color' => 'achtergrondkleur van sectie',
            'section_spacing' => 'ruimte boven/onder',
            'title' => 'titel',
            'subtitle' => 'subtitel',
            'description' => 'omschrijving',
            'columns' => 'aantal kolommen',
            'links' => 'links',
            'product_count' => 'aantal producten',
            'section_background_type' => 'achtergrondtype van sectie',
            'section_background_shape_color' => 'kleur van decoratieve vorm',
            'text_background_color' => 'achtergrondkleur van tekstvlak',
            'text_color' => 'tekstkleur',
            'blocks_per_row' => 'blokken per rij',
            'label_enabled' => 'label tonen',
            'label' => 'label',
            'url' => 'link',
            'target_blank' => 'link openen in',
            'button_enabled' => 'knop tonen',
            'link_area' => 'link toepassen op',
            'button_text' => 'knoptekst',
            'button_label' => 'knoptekst',
            'button_link' => 'knoplink',
            'button_link_label' => 'label voor knoplink',
            'button_target_blank' => 'knoplink openen in',
            'button_color' => 'knopkleur',
            'button_text_color' => 'tekstkleur van knop',
            'content_alignment' => 'uitlijning',
            'login_enabled' => 'loginlink tonen',
            'login_text' => 'logintekst',
            'login_link_text' => 'loginlink tekst',
            'media' => 'afbeelding',
            'type' => 'type',
        ],
    ],
];
