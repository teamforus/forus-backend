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

    'header'               => 'The given data was invalid.',
    "prohibited"            => "The :attribute field is prohibited.",
    "prohibited_if"         => "The :attribute field is prohibited when :other is :value.",
    "prohibited_unless"     => "The :attribute field is prohibited unless :other is in :values.",
    "prohibits"             => "The :attribute field prohibits :other from being present.",

    'accepted'             => 'The :attribute must be accepted.',
    'active_url'           => 'The :attribute is not a valid URL.',
    'after'                => 'The :attribute must be a date after :date.',
    'after_or_equal'       => 'The :attribute must be a date after or equal to :date.',
    'alpha'                => 'The :attribute may only contain letters.',
    'alpha_dash'           => 'The :attribute may only contain letters, numbers, and dashes.',
    'alpha_num'            => 'The :attribute may only contain letters and numbers.',
    'array'                => 'The :attribute must be an array.',
    'before'               => 'The :attribute must be a date before :date.',
    'before_or_equal'      => 'The :attribute must be a date before or equal to :date.',
    'between'              => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'file'    => 'The :attribute must be between :min and :max kilobytes.',
        'string'  => 'The :attribute must be between :min and :max characters.',
        'array'   => 'The :attribute must have between :min and :max items.',
    ],
    'boolean'              => 'The :attribute field must be true or false.',
    'confirmed'            => 'The :attribute confirmation does not match.',
    'date'                 => 'The :attribute is not a valid date.',
    'date_format'          => 'The :attribute does not match the format :format.',
    'different'            => 'The :attribute and :other must be different.',
    'digits'               => 'The :attribute must be :digits digits.',
    'digits_between'       => 'The :attribute must be between :min and :max digits.',
    'dimensions'           => 'The :attribute has invalid image dimensions.',
    'distinct'             => 'The :attribute field has a duplicate value.',
    'email'                => 'The :attribute must be a valid email address.',
    'exists'               => 'The selected :attribute is invalid.',
    'file'                 => 'The :attribute must be a file.',
    'filled'               => 'The :attribute field must have a value.',
    'gt' => [
        'numeric'   => 'The :attribute must be greater than :value.',
        'file'      => 'The :attribute must be greater than :value kilobytes.',
        'string'    => 'The :attribute must be greater than :value characters.',
        'array'     => 'The :attribute must have more than :value items.',
    ],
    'gte' => [
        'numeric'   => 'The :attribute must be greater than or equal :value.',
        'file'      => 'The :attribute must be greater than or equal :value kilobytes.',
        'string'    => 'The :attribute must be greater than or equal :value characters.',
        'array'     => 'The :attribute must have :value items or more.',
    ],
    'image'                => 'The :attribute must be an image.',
    'in'                   => 'The selected :attribute is invalid.',
    'in_array'             => 'The :attribute field does not exist in :other.',
    'integer'              => 'The :attribute must be an integer.',
    'ip'                   => 'The :attribute must be a valid IP address.',
    'ipv4'                 => 'The :attribute must be a valid IPv4 address.',
    'ipv6'                 => 'The :attribute must be a valid IPv6 address.',
    'json'                 => 'The :attribute must be a valid JSON string.',
    'lt' => [
        'numeric' => 'The :attribute must be less than :value.',
        'file' => 'The :attribute must be less than :value kilobytes.',
        'string' => 'The :attribute must be less than :value characters.',
        'array' => 'The :attribute must have less than :value items.',
    ],
    'lte' => [
        'numeric'   => 'The :attribute must be less than or equal :value.',
        'file'      => 'The :attribute must be less than or equal :value kilobytes.',
        'string'    => 'The :attribute must be less than or equal :value characters.',
        'array'     => 'The :attribute must not have more than :value items.',
    ],
    'max'                  => [
        'numeric' => 'The :attribute may not be greater than :max.',
        'file'    => 'The :attribute may not be greater than :max kilobytes.',
        'string'  => 'The :attribute may not be greater than :max characters.',
        'array'   => 'The :attribute may not have more than :max items.',
    ],
    'mimes'                => 'The :attribute must be a file of type: :values.',
    'mimetypes'            => 'The :attribute must be a file of type: :values.',
    'min'                  => [
        'numeric' => 'The :attribute must be at least :min.',
        'file'    => 'The :attribute must be at least :min kilobytes.',
        'string'  => 'The :attribute must be at least :min characters.',
        'array'   => 'The :attribute must have at least :min items.',
    ],
    'not_in'               => 'The selected :attribute is invalid.',
    'numeric'              => 'The :attribute must be a number.',
    'present'              => 'The :attribute field must be present.',
    'regex'                => 'The :attribute format is invalid.',
    'required'             => 'The :attribute field is required.',
    'required_if'          => 'The :attribute field is required when :other is :value.',
    'required_unless'      => 'The :attribute field is required unless :other is in :values.',
    'required_with'        => 'The :attribute field is required when :values is present.',
    'required_with_all'    => 'The :attribute field is required when :values is present.',
    'required_without'     => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same'                 => 'The :attribute and :other must match.',
    'size'                 => [
        'numeric' => 'The :attribute must be :size.',
        'file'    => 'The :attribute must be :size kilobytes.',
        'string'  => 'The :attribute must be :size characters.',
        'array'   => 'The :attribute must contain :size items.',
    ],
    'string'               => 'The :attribute must be a string.',
    'timezone'             => 'The :attribute must be a valid zone.',
    'unique'               => 'The :attribute has already been taken.',
    'uploaded'             => 'The :attribute failed to upload.',
    'url'                  => 'The :attribute must start with http:// or https://.',

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
    | of 'email'. This simply helps us make messages a little cleaner.
    |
    */
    'old_pin_code'          => 'Old pin code don\'t match.',
    'unknown_record_key'    => 'Unknown record key: ":key".',
    'unique_record'         => 'The :attribute record has already been taken.',

    'organization_fund'     => [
        'wrong_categories'  => 'validation.organization_fund.wrong_categories',
        'already_requested' => 'validation.organization_fund.already_requested',
    ],
    'employees' => [
        'employee_already_exists' => 'An employee with the same email address already exists.',
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
    'prevalidation_missing_required_keys' => 'The file does not contain all required fields.',
    'prevalidation_invalid_record_key' => 'A field type included in the file does not exist.',
    'validation.prevalidation_invalid_type_primary_email' => 'The primary email address field is a system field and cannot be used here.',
    'validation.prevalidation_missing_primary_key' => 'The key field is missing from the file.',
    'fund_request_request_field_incomplete' => 'Type a message. This field cannot be empty',
    'email_already_used' => 'This emailaddress is already used by another user',
    'iban' => 'The IBAN-number is mandatory and must be valid.',
    'kvk' => 'The KVK-number is mandatory and must be valid.',
    'business_type_id' => 'Organization type',
    'voucher' => [
        'expired' => 'This voucher is expired.',
        'pending' => 'This voucher is pending.',
        'deactivated' => 'The QR code is no longer valid since :deactivation_date.',
        'product_voucher_used' => 'This product voucher is already used.',
        'provider_not_applied' => 'You can not scan this voucher! You are not applied for this fund.',
        'provider_pending' => 'You can not scan this voucher! Your application for this fund is still pending.',
        'provider_denied' => 'You can not scan this voucher! Your application for this fund is denied.',
        'fund_not_active' => 'Your can not scan this voucher! The fund is not active (anymore).',
        'not_enough_funds' => 'Not enough credit on voucher.',
        'product_sold_out' => 'Your offer is sold out, increase the number that is still for sale in your management environment.',
        'reservation_used' => 'The voucher is already used.',
        'reservation_product_removed' => 'The product removed by the provider.',
    ],
    'product_voucher' => [
        'product_not_found' => 'Invalid product id.',
        'product_sold_out' => 'Product sold out.',
        'not_enough_stock' => 'Not enough stock available for product :product_name.',
        'reservation_used' => 'The voucher is already used.',
        'reservation_product_removed' => 'The product removed by the provider.',
    ],
    'product_reservation' => [
        'product_not_found' => 'Invalid product id.',
        'product_sold_out' => 'Product sold out.',
        'not_enough_stock' => 'Not enough stock available for product :product_name.',
        'reservation_not_enabled' => 'Reservation is not available for this product.',
        'no_identity_stock' => 'Limit stock per user reached.',
        'no_total_stock' => 'Limit stock per user reached.',
        'reservation_not_pending' => join(" ", [
            'The reservation (#:code) is not pending, the current state is ":state".',
            'Please go to the dashboard to review and accept the reservation.'
        ]),
        'too_many_canceled_reservations_for_product' => 'There are too many canceled reservations',
        'too_many_reservation_requests_for_product' => 'There are too many reservation requests for this product',
    ],
    'attributes' => [
        'pin_code' => "Pin code",
        'records' => "Records",
        'email' => "e-mail",
        'primary_email' => 'e-mail',
        'records.primary_email' => 'e-mail',
        'records.bsn' => 'bsn',
    ],
    'voucher_generator' => [
        'budget_exceeded' => 'The sum of the vouchers amount exceeds budget left on the fund',
    ],
];
