<?php

namespace Tests\TestCases;

class SponsorFinancialStatisticsTestCases
{
    /** @var array|array[] */
    public static array $testCaseFundStatisticMonth = [
        'type' => 'month',
        'date' => '2021-01-01',
        'funds' => [[
            'name' => 'Test fund 1',
            'date' => '2021-04-23',
            'closed' => true,
            'vouchers' => [[
                'type' => 'budget',
                'transaction_amount' => 20,
            ], [
                'type' => 'budget',
                'transaction_amount' => 20,
            ]],
        ], [
            'name' => 'Test fund 2',
            'date' => '2021-06-23',
            'vouchers' => [[
                'type' => 'budget',
                'transaction_amount' => 24,
            ], [
                'type' => 'product',
                'product_price' => 14,
                'category' => 'test_category_el2',
                'provider' => 'test_provider_month',
                'business_type' => 'test_business_type',
                'provider_office_postcode_number' => '123456789',
            ], [
                'type' => 'budget',
                'transaction_amount' => 24,
            ]],
        ], [
            'name' => 'Test fund 3',
            'date' => '2021-06-13',
            'vouchers' => [[
                'type' => 'budget',
                'transaction_amount' => 29,
            ], [
                'type' => 'product',
                'product_price' => 29,
                'category' => 'test_category_el',
                'provider' => 'test_provider_month2',
                'business_type' => 'test_business_type',
                'provider_office_postcode_number' => '123456789',
            ]],
        ]],
        'assert' => [
            'date' => '2021-06-01',
            'count' => 5,
            'amount' => 120,
            'highest_transaction' => 29,
            'highest_daily_transaction' => 62,
            'highest_daily_transaction_date' => '2021-06-23',
            'filters' => [
                'categories' => [
                    'test_category_el' => 1,
                ],
                'business_types' => [
                    'test_business_type' => 2,
                ],
                'postcodes' => [
                    '123456789' => 1
                ],
                'funds' => [
                    'Test fund 1' => 0,
                    'Test fund 2' => 3,
                    'Test fund 3' => 2,
                ],
            ],
            'transactions' => [
                '2021-06-13' => [
                    'count' => 2,
                    'amount' => 58,
                ],
                '2021-06-23' => [
                    'count' => 3,
                    'amount' => 62,
                ]
            ],
            'providers' => [
                'test_provider_month' => [
                    'count' => 1,
                    'total_spent' => 14,
                    'highest_transaction' => 14,
                    'transactions' => [[
                        'amount' => 14
                    ]],
                ],
                'test_provider_month2' => [
                    'count' => 1,
                    'total_spent' => 29,
                    'highest_transaction' => 29,
                    'transactions' => [[
                        'amount' => 29
                    ]],
                ],
            ],
        ],

        'filters' => [[
            'params' => [
                'product_categories' => ['test_category_el'],
                'business_types' => [],
                'postcodes' => [],
                'funds' => [],
            ],
            'assert' => [
                'date' => '2021-06-01',
                'count' => 1,
                'amount' => 29,
                'highest_transaction' => 29,
                'highest_daily_transaction' => 29,
                'highest_daily_transaction_date' => '2021-06-13',
                'transactions' => [
                    '2021-06-13' => [
                        'count' => 1,
                        'amount' => 29,
                    ],
                ],
            ],
        ], [
            'params' => [
                'product_categories' => [],
                'business_types' => ['test_business_type'],
                'postcodes' => [],
                'funds' => [],
            ],
            'assert' => [
                'date' => '2021-06-01',
                'count' => 2,
                'amount' => 43,
                'highest_transaction' => 29,
                'highest_daily_transaction' => 29,
                'highest_daily_transaction_date' => '2021-06-13',
                'transactions' => [
                    '2021-06-13' => [
                        'count' => 1,
                        'amount' => 29,
                    ],
                    '2021-06-23' => [
                        'count' => 1,
                        'amount' => 14,
                    ],
                ],
            ],
        ], [
            'params' => [
                'product_categories' => [],
                'business_types' => [],
                'postcodes' => [],
                'funds' => ['Test fund 2'],
            ],
            'assert' => [
                'date' => '2021-06-01',
                'count' => 3,
                'amount' => 62,
                'highest_transaction' => 24,
                'highest_daily_transaction' => 62,
                'highest_daily_transaction_date' => '2021-06-23',
                'transactions' => [
                    '2021-06-23' => [
                        'count' => 3,
                        'amount' => 62,
                    ],
                ],
            ],
        ], [
            'params' => [
                'product_categories' => [],
                'business_types' => [],
                'postcodes' => ['123456789'],
                'funds' => [],
            ],
            'assert' => [
                'date' => '2021-06-01',
                'count' => 2,
                'amount' => 43,
                'highest_transaction' => 29,
                'highest_daily_transaction' => 29,
                'highest_daily_transaction_date' => '2021-06-13',
                'transactions' => [
                    '2021-06-13' => [
                        'count' => 1,
                        'amount' => 29,
                    ],
                    '2021-06-23' => [
                        'count' => 1,
                        'amount' => 14,
                    ],
                ],
            ],
        ], [
            'params' => [
                'product_categories' => [],
                'business_types' => [],
                'postcodes' => ['1111111'],
                'funds' => [],
            ],
            'assert' => [
                'date' => '2021-06-01',
                'count' => 0,
                'amount' => 0,
                'highest_transaction' => null,
                'highest_daily_transaction' => null,
                'highest_daily_transaction_date' => null,
                'transactions' => [],
            ],
        ]],
    ];

    /** @var array|array[] */
    public static array $testCaseFundStatisticQuarter = [
        'type' => 'quarter',
        'date' => '2022-01-01',
        'funds' => [[
            'name' => 'Test fund quarter 1',
            'date' => '2022-01-23',
            'vouchers' => [[
                'type' => 'budget',
                'transaction_amount' => 12,
            ], [
                'type' => 'product',
                'product_price' => 25,
                'category' => 'test_category_el_quarter',
                'provider' => 'test_provider_quarter',
                'business_type' => 'test_business_type_quarter',
                'provider_office_postcode_number' => '12345678910',
            ], [
                'type' => 'budget',
                'transaction_amount' => 27,
            ]],
        ], [
            'name' => 'Test fund quarter 2',
            'date' => '2022-02-23',
            'closed' => true,
            'vouchers' => [[
                'type' => 'budget',
                'transaction_amount' => 23,
            ], [
                'type' => 'product',
                'product_price' => 11,
                'category' => 'test_category_el_quarter',
                'provider' => 'test_provider_quarter',
                'business_type' => 'test_business_type_quarter',
                'provider_office_postcode_number' => '12345678910',
            ], [
                'type' => 'product',
                'product_price' => 25,
                'category' => 'test_category_el_quarter2',
                'provider' => 'test_provider_quarter',
                'business_type' => 'test_business_type_quarter',
                'provider_office_postcode_number' => '1234567891011',
            ], [
                'type' => 'budget',
                'transaction_amount' => 33,
            ]],
        ]],
        'assert' => [
            'date' => '2022-01-01',
            'count' => 7,
            'amount' => 156,
            'highest_transaction' => 33,
            'highest_daily_transaction' => 92,
            'highest_daily_transaction_date' => '2022-02-23',
            'filters' => [
                'categories' => [
                    'test_category_el_quarter' => 2,
                    'test_category_el_quarter2' => 1,
                ],
                'business_types' => [
                    'test_business_type_quarter' => 3,
                ],
                'postcodes' => [
                    '12345678910' => 3,
                ],
                'funds' => [
                    'Test fund quarter 1' => 3,
                    'Test fund quarter 2' => 4,
                ],
            ],
            'transactions' => [
                '2022-01-20' => [
                    'count' => 3,
                    'amount' => 64,
                ],
                '2022-02-23' => [
                    'count' => 4,
                    'amount' => 92,
                ],
            ],
            'providers' => [
                'test_provider_quarter' => [
                    'count' => 3,
                    'total_spent' => 61,
                    'highest_transaction' => 25,
                    'transactions' => [[
                        'amount' => 11,
                    ], [
                        'amount' => 25,
                    ], [
                        'amount' => 25,
                    ]],
                ],
            ],
        ],

        'filters' => [[
            'params' => [
                'product_categories' => ['test_category_el_quarter'],
                'business_types' => [],
                'postcodes' => [],
                'funds' => [],
            ],
            'assert' => [
                'date' => '2022-01-01',
                'count' => 2,
                'amount' => 36,
                'highest_transaction' => 25,
                'highest_daily_transaction' => 25,
                'highest_daily_transaction_date' => '2022-01-23',
                'transactions' => [
                    '2022-01-23' => [
                        'count' => 1,
                        'amount' => 25,
                    ],
                    '2022-02-23' => [
                        'count' => 1,
                        'amount' => 11,
                    ],
                ],
            ],
        ], [
            'params' => [
                'product_categories' => [],
                'business_types' => ['test_business_type_quarter'],
                'postcodes' => [],
                'funds' => [],
            ],
            'assert' => [
                'date' => '2022-01-01',
                'count' => 3,
                'amount' => 61,
                'highest_transaction' => 25,
                'highest_daily_transaction' => 36,
                'highest_daily_transaction_date' => '2022-02-23',
                'transactions' => [
                    '2022-01-23' => [
                        'count' => 1,
                        'amount' => 25,
                    ],
                    '2022-02-23' => [
                        'count' => 2,
                        'amount' => 36,
                    ],
                ],
            ],
        ], [
            'params' => [
                'product_categories' => [],
                'business_types' => [],
                'postcodes' => [],
                'funds' => ['Test fund quarter 1'],
            ],
            'assert' => [
                'date' => '2022-01-01',
                'count' => 3,
                'amount' => 64,
                'highest_transaction' => 27,
                'highest_daily_transaction' => 64,
                'highest_daily_transaction_date' => '2022-01-23',
                'transactions' => [
                    '2022-01-23' => [
                        'count' => 3,
                        'amount' => 64,
                    ],
                ],
            ],
        ], [
            'params' => [
                'product_categories' => [],
                'business_types' => [],
                'postcodes' => ['12345678910'],
                'funds' => [],
            ],
            'assert' => [
                'date' => '2022-01-01',
                'count' => 3,
                'amount' => 61,
                'highest_transaction' => 25,
                'highest_daily_transaction' => 36,
                'highest_daily_transaction_date' => '2022-02-23',
                'transactions' => [
                    '2022-01-23' => [
                        'count' => 1,
                        'amount' => 25,
                    ],
                    '2022-02-23' => [
                        'count' => 2,
                        'amount' => 36,
                    ],
                ],
            ],
        ], [
            'params' => [
                'product_categories' => [],
                'business_types' => [],
                'postcodes' => ['1111111'],
                'funds' => [],
            ],
            'assert' => [
                'date' => '2022-01-01',
                'count' => 0,
                'amount' => 0,
                'highest_transaction' => null,
                'highest_daily_transaction' => null,
                'highest_daily_transaction_date' => null,
                'transactions' => [],
            ],
        ]],
    ];

    /** @var array|array[] */
    public static array $testCaseFundStatisticYear = [
        'type' => 'year',
        'date' => '2022-01-01',
        'funds' => [[
            'name' => 'Test fund year 1',
            'date' => '2022-01-23',
            'vouchers' => [[
                'type' => 'budget',
                'transaction_amount' => 12,
            ], [
                'type' => 'product',
                'product_price' => 25,
                'category' => 'test_category_el_year',
                'provider' => 'test_provider_year',
                'business_type' => 'test_business_type_year',
                'provider_office_postcode_number' => '12345678910',
            ], [
                'type' => 'budget',
                'transaction_amount' => 27,
            ]],
        ], [
            'name' => 'Test fund year 2',
            'date' => '2022-06-23',
            'closed' => true,
            'vouchers' => [[
                'type' => 'budget',
                'transaction_amount' => 23,
            ], [
                'type' => 'product',
                'product_price' => 11,
                'category' => 'test_category_el_year',
                'provider' => 'test_provider_year',
                'business_type' => 'test_business_type_year',
                'provider_office_postcode_number' => '12345678910',
            ], [
                'type' => 'product',
                'product_price' => 25,
                'category' => 'test_category_el_year2',
                'provider' => 'test_provider_year',
                'business_type' => 'test_business_type_year',
                'provider_office_postcode_number' => '1234567891011',
            ], [
                'type' => 'budget',
                'transaction_amount' => 33,
            ]],
        ]],
        'assert' => [
            'date' => '2022-01-01',
            'count' => 7,
            'amount' => 156,
            'highest_transaction' => 33,
            'highest_daily_transaction' => 92,
            'highest_daily_transaction_date' => '2022-06-23',
            'filters' => [
                'categories' => [
                    'test_category_el_year' => 2,
                    'test_category_el_year2' => 1,
                ],
                'business_types' => [
                    'test_business_type_year' => 3,
                ],
                'postcodes' => [
                    '12345678910' => 3,
                ],
                'funds' => [
                    'Test fund year 1' => 3,
                    'Test fund year 2' => 4,
                ],
            ],
            'transactions' => [
                '2022-01-20' => [
                    'count' => 3,
                    'amount' => 64,
                ],
                '2022-06-23' => [
                    'count' => 4,
                    'amount' => 92,
                ],
            ],
            'providers' => [
                'test_provider_year' => [
                    'count' => 3,
                    'total_spent' => 61,
                    'highest_transaction' => 25,
                    'transactions' => [[
                        'amount' => 11,
                    ], [
                        'amount' => 25,
                    ], [
                        'amount' => 25,
                    ]],
                ],
            ],
        ],

        'filters' => [[
            'params' => [
                'product_categories' => ['test_category_el_year'],
                'business_types' => [],
                'postcodes' => [],
                'funds' => [],
            ],
            'assert' => [
                'date' => '2022-01-01',
                'count' => 2,
                'amount' => 36,
                'highest_transaction' => 25,
                'highest_daily_transaction' => 25,
                'highest_daily_transaction_date' => '2022-01-23',
                'transactions' => [
                    '2022-01-23' => [
                        'count' => 1,
                        'amount' => 25,
                    ],
                    '2022-06-23' => [
                        'count' => 1,
                        'amount' => 11,
                    ],
                ],
            ],
        ], [
            'params' => [
                'product_categories' => [],
                'business_types' => ['test_business_type_year'],
                'postcodes' => [],
                'funds' => [],
            ],
            'assert' => [
                'date' => '2022-01-01',
                'count' => 3,
                'amount' => 61,
                'highest_transaction' => 25,
                'highest_daily_transaction' => 36,
                'highest_daily_transaction_date' => '2022-06-23',
                'transactions' => [
                    '2022-01-23' => [
                        'count' => 1,
                        'amount' => 25,
                    ],
                    '2022-06-23' => [
                        'count' => 2,
                        'amount' => 36,
                    ],
                ],
            ],
        ], [
            'params' => [
                'product_categories' => [],
                'business_types' => [],
                'postcodes' => [],
                'funds' => ['Test fund year 1'],
            ],
            'assert' => [
                'date' => '2022-01-01',
                'count' => 3,
                'amount' => 64,
                'highest_transaction' => 27,
                'highest_daily_transaction' => 64,
                'highest_daily_transaction_date' => '2022-01-23',
                'transactions' => [
                    '2022-01-23' => [
                        'count' => 3,
                        'amount' => 64,
                    ],
                ],
            ],
        ], [
            'params' => [
                'product_categories' => [],
                'business_types' => [],
                'postcodes' => ['12345678910'],
                'funds' => [],
            ],
            'assert' => [
                'date' => '2022-01-01',
                'count' => 3,
                'amount' => 61,
                'highest_transaction' => 25,
                'highest_daily_transaction' => 36,
                'highest_daily_transaction_date' => '2022-06-23',
                'transactions' => [
                    '2022-01-23' => [
                        'count' => 1,
                        'amount' => 25,
                    ],
                    '2022-06-23' => [
                        'count' => 2,
                        'amount' => 36,
                    ],
                ],
            ],
        ], [
            'params' => [
                'product_categories' => [],
                'business_types' => [],
                'postcodes' => ['1111111'],
                'funds' => [],
            ],
            'assert' => [
                'date' => '2022-01-01',
                'count' => 0,
                'amount' => 0,
                'highest_transaction' => null,
                'highest_daily_transaction' => null,
                'highest_daily_transaction_date' => null,
                'transactions' => [],
            ],
        ]],
    ];
}
