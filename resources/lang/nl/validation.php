<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

	'accepted'             => ':attribute dient te worden geaccepteerd.',
	'active_url'           => ':attribute is geen geldige URL.',
	'after'                => ':attribute dient een datum te zijn na :date.',
    'after_or_equal'       => ':attribute dient een datum gelijkwaardig of later te zijn dan :date',
	'alpha'                => ':attribute mag alleen letters bevatten.',
	'alpha_dash'           => ':attribute mag alleen letters, nummers, and strepen bevatten.',
	'alpha_num'            => ':attribute mag alleen letters en nummers bevatten.',
	'array'                => ':attribute moet een array zijn.',
	'before'               => ':attribute moet een datum zijn eerder dan :date.',
    'before_or_equal'      => ':attribute moet een datum voor of gelijk zijn aan :date.',
	'between'              => [
		'numeric' => ':attribute moet tussen :min en :max liggen.',
		'file'    => ':attribute moet tussen :min en :max kilobytes zijn.',
		'string'  => ':attribute moet tussen :min en :max karakters lang zijn.',
		'array'   => ':attribute moet tussen :min en :max items bevatten.',
	],
 	'boolean'              => ':attribute kan enkel true of false zijn.',
	'confirmed'            => ':attribute bevestiging komt niet overeen.',
	'date'                 => ':attribute is geen geldige datum.',
	'date_format'          => ':attribute komt niet overeen met het formaat :format.',
	'different'            => ':attribute en :other dienen verschillend te zijn.',
	'digits'               => ':attribute moet :digits cijfers zijn.',
	'digits_between'       => ':attribute moet tussen :min en :max cijfers zijn.',
    'dimensions'           => ':attribute heeft een verkeerd formaat.',
    'distinct'             => ':attribute heeft dubbele waarde',
    'email'                => ':attribute moet een geldig e-mail adres zijn',
    'exists'               => 'geselecteerde :attribute is ongeldig',
    'file'                 => ':attribute moet een bestand zijn',
    'filled'               => ':attribute veld moet een waarde bevatten.',
    'image'                => ':attribute moet een afbeelding zijn',
    'in'                   => 'geselecteerde :attribute is ongeldig.',
    'in_array'             => ':attribute veld bestaat niet in :other.',
    'integer'              => ':attribute moet integer zijn',
	'ip'                   => ':attribute dient een geldig IP adres te zijn.',
    'ipv4'                 => ':attribute dient een geldig IPv4 adres te zijn.',
    'ipv6'                 => ':attribute dient een geldig IPv6 adres te zijn.',
    'json'                 => ':attribute dient een geldig JSON string te zijn.',
	'max'                  => [
		'numeric' => ':attribute mag niet groter zijn dan :max.',
		'file'    => ':attribute mag niet groter zijn dan :max kilobytes.',
		'string'  => ':attribute mag niet groter zijn dan :max karakters.',
		'array'   => ':attribute mag niet meer dan :max items bevatten.',
	],
	'mimes'                => ':attribute dient een bestand te zijn van het type: :values.',
    'mimetypes'            => ':attribute dient een bestand te zijn van het type: :values.',
	'min'                  => [
		'numeric' => ':attribute dient minimaal :min te zijn.',
		'file'    => ':attribute dient minimaal :min kilobytes te zijn.',
		'string'  => ':attribute dient minimaal :min karakters te bevatten.',
		'array'   => ':attribute dient minimaal :min items te bevatten.',
	],
	'not_in'               => 'Het geselecteerde :attribute is ongeldig.',
	'numeric'              => 'Het :attribute dient een nummer te zijn.',
    'present'              => 'Het :attribute veld moet aanwezig zijn.',
	'regex'                => 'Het :attribute formaat is ongeldig.',
	'required'             => 'Het :attribute veld is verplicht.',
	'required_if'          => 'Het :attribute veld is verplicht wanneer :other is :value.',
	'required_with'        => 'Het :attribute veld is verplicht wanneer :values aanwezig is.',
  'required_unless'      => 'Het :attribute veld in nodig tenzij :other in :values zit.',
	'required_with_all'    => 'Het :attribute veld is verplicht wanneer :values aanwezig is.',
	'required_without'     => 'Het :attribute veld is verplicht wanneer :values niet aanwezig is.',
	'required_without_all' => 'Het :attribute veld is verplicht wanneer geen van :values aanwezig is.',
	'same'                 => 'Het :attribute en :other moeten hetzelfde zijn.',
	'size'                 => [
		'numeric' => ':attribute moet :size zijn.',
		'file'    => ':attribute moet :size kilobytes groot zijn.',
		'string'  => ':attribute moet :size karakters lang zijn.',
		'array'   => ':attribute moet :size items bevatten.',
	],
    'string'               => 'Het :attribute moet een reeks zijn.',
	'timezone'             => ':attribute moet een geldige tijdszone zijn.',
	'unique'               => ':attribute is al bezet.',
    'uploaded'             => 'Het is niet gelukt om :attribute te uploaden',
	'url'                  => ':attribute moet beginnen met http: // of https: //.',

    // Custom
    'old_pin_code'          => 'De oude en nieuwe inlogcode komen niet overeen.',
    'unknown_record_key'    => 'Unknown record key: ":key".',
    'unique_record'         => 'Het :attribute eigenschap is al gekozen.',

    'organization_fund'     => [
        'wrong_categories'  => 'validation.organization_fund.wrong_categories',
        'already_requested' => 'validation.organization_fund.already_requested',
    ],
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention 'attribute.rule' to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of 'email'. This simply helps us make messages a little cleaner.
    |
    */
    'prevalidation_missing_required_keys' => 'Het bestand bevat niet alle verplichte velden.',
    'prevalidation_invalid_record_key' => 'Een veldtype dat is opgenomen in het bestand bestaat niet.',
    'validation.prevalidation_invalid_type_primary_email' => 'Het primaire e-mailadres veld is een systeemveld en kan hier niet gebruikt worden.',
    'validation.prevalidation_missing_primary_key' => 'Het sleutelveld ontbreekt in het bestand.',
    'iban' => 'Het IBAN-nummer is verplicht en moet geldig zijn.',
    'kvk' => 'Het KVK-nummer is verplicht en moet geldig zijn.',
    'business_type_id' => 'Organisatie type',
    'voucher' => [
        'expired' => 'De voucher is verlopen.',
        'product_voucher_used' => 'De voucher voor deze aanbieding is al gebruikt!',
        'provider_not_applied' => 'U mag deze voucher niet scannen! Uw organisatie is nog niet aangemeld bij het fonds van deze voucher.',
        'provider_pending' => 'U mag deze voucher niet scannen! Status voor aanmelding van het fonds van deze voucher is wachtend.',
        'provider_declined' => 'U mag deze voucher niet scannen! Uw organisatie is geweigerd om deel te nemen aan het fonds. Zoek contact op met sponsor voor een reden.',
        'fund_not_active' => 'U mag deze voucher nog niet scannen! Het fonds is niet actief.',
        'not_enough_funds' => 'Onvoldoende tegoed op de voucher.',
    ],
    'attributes' => [
        'pin_code' => 'pincode',
        'records' => 'Records',
        'email' => 'e-mail',
        'primary_email' => 'e-mail',
        'records.primary_email' => 'e-mailadres',
        'name' => 'naam',
        'phone' => 'telefoonnummer',
        'iban' => 'IBAN-nummer',
	'btw' => 'BTW-nummer',
	'kvk' => 'KvK-nummer',
        'code' => 'activatiecode',
	'note' => 'notitie',
	'amount' => 'hoeveelheid',
	'product_categories' => 'categorieën',
	'old_price' => 'oude prijs',
	'description' => 'omschrijving',
	'price' => 'prijs',
	'total_amount' => 'aanbod',
	'expire_at' => 'verloopdatum',
	'today' => 'vandaag',
	'product_category_id' => 'categorie',
	'address' => 'adres',
	'schedule' => 'openingstijden',
	'state' => 'status',
	'start_date' => 'startdatum',
	'end_date' => 'einddatum',
    ],
    'owner_cant_be_employee' => 'De aanbieder kan niet toegevoegd worden als medewerker'

];
