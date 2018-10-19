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

    'accepted'             => 'Het :attribute moet worden geaccepteerd.',
    'active_url'           => 'Het :attribute is geen geldige URL.',
    'after'                => 'het :attribute moet een datum zijn na :date',
    'after_or_equal'       => 'Het :attribute moet een datum gelijkwaardig of later dan :date zijn.',
    'alpha'                => 'Het :attribute mag alleen letters bevatten.',
    'alpha_dash'           => 'Het :attribute mag alleen letters, cijfers en streepjes bevatten.',
    'alpha_num'            => 'Het :attribute mag alleen letters en cijfers bevatten.',
    'array'                => 'Het :attribute moet een array zijn.',
    'before'               => 'Het :attribute moet een datum voor :date zijn.',
    'before_or_equal'      => 'Het :attribute moet een datum voor of gelijk aan :date.',
    'between'              => [
        'numeric' => 'Het :attribute moet tussen :min en :max zijn.',
        'file'    => 'Het :attribute moet tussen :min en :max kilobytes zijn.',
        'string'  => 'Het :attribute moet tussen :min en :max karakters zijn.',
        'array'   => 'Het :attribute moet tussen :min en :max items zijn.',
    ],
    'boolean'              => 'Het :attribute veld moet true of false zijn.',
    'confirmed'            => 'De :attribute bevestiging komt niet overeen',
    'date'                 => 'De :attribute is geen geldige datum',
    'date_format'          => 'Het :attribute voldoet in aan de voorwaarden van :format.',
    'different'            => 'Het :attribute en :other moeten van elkaar verschillen',
    'digits'               => 'Het :attribute moeten :digits getallen zijn.',
    'digits_between'       => 'Het :attribute ligt tussen de :min en :max cijfers',
    'dimensions'           => 'Het :attribute heeft een verkeerd formaat.',
    'distinct'             => 'Het :attribute heeft dubbele waarde',
    'email'                => 'Het :attribute moet een geldig e-mail adres zijn',
    'exists'               => 'Het geselecteerde :attribute is ongeldig',
    'file'                 => 'Het :attribute moet een bestand zijn',
    'filled'               => 'Het :attribute veld moet een waarde bevatten.',
    'image'                => 'Het :attribute moet een afbeelding zijn',
    'in'                   => 'De geselecteerde :attribute is ongeldig.',
    'in_array'             => 'Het :attribute veld bestaat niet in :other.',
    'integer'              => 'Het :attribute moet integer zijn',
    'ip'                   => 'Het :attribute moet een geldig IP adres zijn.',
    'ipv4'                 => 'Het :attribute moet een geldig IPv4 adres zijn.',
    'ipv6'                 => 'Het :attribute moet een geldig IPv6 adres zijn.',
    'json'                 => 'Het :attribute moet een geldig JSON string.',
    'max'                  => [
        'numeric' => 'Het :attribute mag niet groter zijn dan :max.',
        'file'    => 'Het :attribute mag niet meer dan :max kilobytes zijn.',
        'string'  => 'Het :attribute mag niet meer dan :max characters bevatten.',
        'array'   => 'Het :attribute mag niet meer dan :max items bevatten.',
    ],
    'mimes'                => 'Het :attribute moet een bestand zijn van het type: :values.',
    'mimetypes'            => 'Hey :attribute moet een bestand zijn van het type: :values.',
    'min'                  => [
        'numeric' => 'Het :attribute moet op zijn minst :min zijn.',
        'file'    => 'Het :attribute moet op zijn minst :min kilobytes zijn.',
        'string'  => 'Het :attribute moet op zijn minst :min characters zijn.',
        'array'   => 'Het :attribute moet op zijn minst :min item zijn.',
    ],
    'not_in'               => 'Een ongeldige :attribute is ingevuld.',
    'numeric'              => 'Het :attribute moet een cijfer zijn.',
    'present'              => 'Het :attribute veld moet aanwezig zijn.',
    'regex'                => 'Het :attribute formaat is ongeldig.',
    'required'             => 'Een :attribute invullen is verplicht.',
    'required_if'          => 'Het :attribute veld is is nodig als :other gelijk is aan :value.',
    'required_unless'      => 'Het :attribute veld in nodig tenzij :other in :values zit.',
    'required_with'        => 'Het :attribute veld is nodig als :values aanwezig is.',
    'required_with_all'    => 'Het :attribute veld is nodig als :values gelijk is.',
    'required_without'     => 'Het :attribute veld is nodig als :values niet aanwezig is.',
    'required_without_all' => 'Het :attribute veld is verplicht als de :values er zijn.',
    'same'                 => 'Het :attribute and :other moeten gelijk aan elkaar zijn.',
    'size'                 => [
        'numeric' => 'Het :attribute moet :size zijn.',
        'file'    => 'Het :attribute moet :size kilobytes zijn.',
        'string'  => 'Het :attribute moet :size characters zijn.',
        'array'   => 'Het :attribute moet op zijn minst :size items zijn.',
    ],
    'string'               => 'Het :attribute moet een reeks zijn.',
    'timezone'             => 'Het :attribute moet een geldig gebied zijn.',
    'unique'               => 'Het :attribute is al gebruikt.',
    'uploaded'             => 'Het is niet gelukt om :attribute te uploaden',
    'url'                  => 'Het :attribute formaat is ongeldig.',

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
    | convention "attribute.rule" to name the lines. This makes it quick to
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
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        'pin_code' => "Pin code",
        'records' => "Records",
        'email' => "e-mailadres",
        'primary_email' => 'e-mailadres',
        'records.primary_email' => 'e-mailadres',
        'kvk' => 'KvK-nummer',
        'name' => 'naam',
        'phone' => 'telefoonnummer',
        'iban' => 'IBAN-nummer',
        'code' => 'Activatiecode',
    ],

];
