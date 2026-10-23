<?php

namespace Tests\Feature;

use App\Helpers\Arr;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundQuery;
use App\Services\Forus\TestData\TestData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\TestsReservations;
use Throwable;

class ProductFundLimitsTest extends TestCase
{
    use MakesTestVouchers;
    use TestsReservations;
    use DatabaseTransactions;

    /**
     * @var array|string[]
     */
    protected array $urls = [
        'organization' => '/api/v1/platform/organizations',
        'fundProviders' => '/api/v1/organizations/:organizationId/provider/funds',
        'products' => '/api/v1/platform/products',
        'provider' => '/api/v1/platform/provider',
    ];

    /**
     * @var Product[]
     */
    protected array $products = [];

    /**
     * @var Identity[]
     */
    protected array $identities = [];

    /**
     * @var Voucher[]
     */
    protected array $vouchers = [];

    /**
     * @var array|array[]
     */
    protected array $testCase1 = [
        'data' => [
            'products' => [[
                'total_amount' => 20,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                    ['fund_id' => 2, 'allow_budget' => true, 'allow_products' => false],
                    ['fund_id' => 3, 'allow_budget' => true, 'allow_products' => true],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 5, 'limit_per_identity' => 2],
                    ['fund_id' => 2, 'limit_total' => 8, 'limit_per_identity' => 3],
                ],
            ], [
                'total_amount' => 30,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                    ['fund_id' => 3, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 5, 'limit_per_identity' => 2],
                    ['fund_id' => 3, 'limit_total' => 15, 'limit_per_identity' => 2],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                    ['amount' => 200, 'fund_id' => 1, 'limit_multiplier' => 2],
                    ['amount' => 200, 'fund_id' => 2, 'limit_multiplier' => 2],
                    ['amount' => 500, 'fund_id' => 3, 'limit_multiplier' => 3],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                    ['fund_id' => 2, 'limit_available' => 6, 'products' => true, 'organizations' => true],
                    ['fund_id' => 3, 'limit_available' => null, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'product' => 1],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                    ['fund_id' => 3, 'limit_available' => 6, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0, 'state' => 'canceled_by_client'],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
                'assert_limits_after' => [
                    ['fund_id' => 1, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0, 'state' => 'rejected'],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
                'assert_limits_after' => [
                    ['fund_id' => 1, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0, 'state' => 'accepted'],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
                'assert_limits_after' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0, ],
                'assert_success' => false,
            ], [
                'data' => ['identity' => 0, 'voucher' => 3, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 3, 'limit_available' => null, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 3, 'product' => 1],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 3, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 3, 'product' => 1],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 3, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'update_limits',
            'actions' => [[
                'product' => 0,
                'identity' => 0,
                'assert_success' => true,
                'limits' => ['fund_id' => 1, 'limit_total' => 10, 'limit_per_identity' => 3],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ], [
                'product' => 1,
                'identity' => 0,
                'assert_success' => true,
                'limits' => ['fund_id' => 1, 'limit_total' => 8, 'limit_per_identity' => 3],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 8, 'products' => true, 'organizations' => true],
                ],
            ], [
                'product' => 1,
                'identity' => 0,
                'assert_success' => true,
                'limits' => ['fund_id' => 3, 'limit_total' => 13, 'limit_per_identity' => 3],
                'assert_limits' => [
                    ['fund_id' => 3, 'limit_available' => 7, 'products' => true, 'organizations' => true],
                ],
            ], [
                'product' => 1,
                'identity' => 0,
                'assert_success' => true,
                'limits' => ['fund_id' => 3, 'limit_total' => 13, 'limit_per_identity' => 1],
                'assert_limits' => [
                    ['fund_id' => 3, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'voucher_transactions',
            'actions' => [
                ['identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => true],
                ['identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => true],
                ['identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => false],
                ['identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => true],
                ['identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => true],
                ['identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => false],
                ['identity' => 0, 'voucher' => 3, 'product' => 1, 'assert_success' => true],
                ['identity' => 0, 'voucher' => 3, 'product' => 1, 'assert_success' => false],
            ],
        ], [
            'type' => 'update_limits',
            'actions' => [[
                'product' => 0,
                'assert_success' => false,
                'assert_errors' => ['enable_products.0'],
                'limits' => ['fund_id' => 1, 'limit_total' => 15, 'limit_per_identity' => 4],
            ], [
                'product' => 1,
                'assert_success' => false,
                'assert_errors' => ['enable_products.0'],
                'limits' => ['fund_id' => 1, 'limit_total' => 33, 'limit_per_identity' => 5],
            ], [
                'product' => 1,
                'assert_success' => false,
                'assert_errors' => ['enable_products.0'],
                'limits' => ['fund_id' => 3, 'limit_total' => 30, 'limit_per_identity' => 4],
            ]],
        ]],
    ];

    protected array $testCase2 = [
        'data' => [
            'products' => [[
                'total_amount' => 15,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 2, 'allow_budget' => true, 'allow_products' => false],
                    ['fund_id' => 3, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 2, 'limit_total' => 8, 'limit_per_identity' => 1],
                    ['fund_id' => 3, 'limit_total' => 10, 'limit_per_identity' => 2],
                ],
            ], [
                'total_amount' => 30,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 3, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 3, 'limit_total' => 15, 'limit_per_identity' => 2],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 200, 'fund_id' => 2, 'limit_multiplier' => 2],
                    ['amount' => 200, 'fund_id' => 2, 'limit_multiplier' => 3],
                    ['amount' => 500, 'fund_id' => 3, 'limit_multiplier' => 1],
                ],
            ], [
                'vouchers' => [
                    ['amount' => 200, 'fund_id' => 2, 'limit_multiplier' => 2],
                    ['amount' => 500, 'fund_id' => 3, 'limit_multiplier' => 3],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['product' => 0, 'identity' => 0],
                'assert_limits' => [
                    ['fund_id' => 2, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                    ['fund_id' => 3, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['product' => 1, 'identity' => 1],
                'assert_limits' => [
                    ['fund_id' => 3, 'limit_available' => 6, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['product' => 1, 'identity' => 0],
                'assert_limits' => [
                    ['fund_id' => 3, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 2, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 1, 'voucher' => 4, 'product' => 1, 'state' => 'canceled_by_client'],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 3, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                ],
                'assert_limits_after' => [
                    ['fund_id' => 3, 'limit_available' => 6, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'update_limits',
            'actions' => [[
                'product' => 0,
                'identity' => 0,
                'assert_success' => true,
                'limits' => ['fund_id' => 2, 'limit_total' => 12, 'limit_per_identity' => 2],
                'assert_limits' => [
                    ['fund_id' => 2, 'limit_available' => 9, 'products' => true, 'organizations' => true],
                ],
            ], [
                'product' => 0,
                'identity' => 0,
                'assert_success' => true,
                'limits' => ['fund_id' => 2, 'limit_total' => 12, 'limit_per_identity' => 1],
                'assert_limits' => [
                    ['fund_id' => 2, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ], [
                'product' => 1,
                'identity' => 1,
                'assert_success' => true,
                'limits' => ['fund_id' => 3, 'limit_total' => 8, 'limit_per_identity' => 2],
                'assert_limits' => [
                    ['fund_id' => 3, 'limit_available' => 6, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'voucher_transactions',
            'actions' => [
                ['identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => true],
                ['identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => false],
                ['identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => true],
                ['identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => true],
                ['identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => true],
                ['identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => false],
                ['identity' => 0, 'voucher' => 2, 'product' => 0, 'assert_success' => true],
                ['identity' => 0, 'voucher' => 2, 'product' => 0, 'assert_success' => true],
                ['identity' => 0, 'voucher' => 2, 'product' => 0, 'assert_success' => false],
                ['identity' => 1, 'voucher' => 4, 'product' => 1, 'assert_success' => true],
                ['identity' => 1, 'voucher' => 4, 'product' => 1, 'assert_success' => true],
                ['identity' => 1, 'voucher' => 4, 'product' => 1, 'assert_success' => true],
                ['identity' => 1, 'voucher' => 4, 'product' => 1, 'assert_success' => true],
                ['identity' => 1, 'voucher' => 4, 'product' => 1, 'assert_success' => true],
                ['identity' => 1, 'voucher' => 4, 'product' => 1, 'assert_success' => true],
                ['identity' => 1, 'voucher' => 4, 'product' => 1, 'assert_success' => false],
            ],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseAvailabilityByIdentityAndFund = [
        'data' => [
            'products' => [[
                'total_amount' => 20,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                    ['fund_id' => 2, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 5, 'limit_per_identity' => 2],
                    ['fund_id' => 2, 'limit_total' => 10, 'limit_per_identity' => 3],
                ],
            ], [
                'total_amount' => 20,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                    ['fund_id' => 2, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 10, 'limit_per_identity' => 1],
                    ['fund_id' => 2, 'limit_total' => 8, 'limit_per_identity' => 2],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 2],
                    ['amount' => 100, 'fund_id' => 2, 'limit_multiplier' => 1],
                ],
            ], [
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                    ['amount' => 100, 'fund_id' => 2, 'limit_multiplier' => 2],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                    ['fund_id' => 2, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 1, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                    ['fund_id' => 2, 'limit_available' => 6, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'product' => 1],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                    ['fund_id' => 2, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 1, 'product' => 1],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                    ['fund_id' => 2, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                    ['fund_id' => 2, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 1],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                    ['fund_id' => 2, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 1, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                    ['fund_id' => 2, 'limit_available' => 6, 'products' => true, 'organizations' => true],
                ],
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseWithoutProductApprovalLimits = [
        'data' => [
            'products' => [[
                'total_amount' => 10,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => true],
                ],
                'limits' => [],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => null, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => null, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => null, 'products' => true, 'organizations' => true],
                ],
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseCancellationRestoresAllowance = [
        'data' => [
            'products' => [[
                'total_amount' => 10,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 2, 'limit_per_identity' => 2],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0, 'state' => 'canceled_by_client'],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
                'assert_limits_after' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseRejectionRestoresAllowance = [
        'data' => [
            'products' => [[
                'total_amount' => 10,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 2, 'limit_per_identity' => 2],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0, 'state' => 'rejected'],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
                'assert_limits_after' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseReservationsAndTransactionsShareAllowance = [
        'data' => [
            'products' => [[
                'total_amount' => 10,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 4, 'limit_per_identity' => 4],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0, 'state' => 'accepted'],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
                'assert_limits_after' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'voucher_transactions',
            'actions' => [[
                'identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ], [
                'identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => false,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseSponsorLimitChangesAfterUsage = [
        'data' => [
            'products' => [[
                'total_amount' => 30,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 4, 'limit_per_identity' => 2],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 2],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'update_limits',
            'actions' => [[
                'product' => 0, 'identity' => 0, 'assert_success' => true,
                'limits' => ['fund_id' => 1, 'limit_total' => 10, 'limit_per_identity' => 2],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                ],
            ], [
                'product' => 0, 'identity' => 0, 'assert_success' => true,
                'limits' => ['fund_id' => 1, 'limit_total' => 3, 'limit_per_identity' => 2],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ], [
                'product' => 0, 'identity' => 0, 'assert_success' => true,
                'limits' => ['fund_id' => 1, 'limit_total' => 10, 'limit_per_identity' => 2],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                ],
            ], [
                'product' => 0, 'identity' => 0, 'assert_success' => true,
                'limits' => ['fund_id' => 1, 'limit_total' => 10, 'limit_per_identity' => 3],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 8, 'products' => true, 'organizations' => true],
                ],
            ], [
                'product' => 0, 'identity' => 0, 'assert_success' => true,
                'limits' => ['fund_id' => 1, 'limit_total' => 10, 'limit_per_identity' => 1],
                'assert_limits' => [[
                    'fund_id' => 1, 'voucher' => 1,
                    'limit_available' => 2, 'products' => true, 'organizations' => true,
                ]],
            ]],
        ], [
            'type' => 'voucher_transactions',
            'actions' => [[
                'identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => false,
                'assert_limits' => [[
                    'fund_id' => 1, 'voucher' => 0,
                    'limit_available' => 2, 'products' => false, 'organizations' => true,
                ]],
            ], [
                'identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [[
                    'fund_id' => 1, 'voucher' => 1,
                    'limit_available' => 1, 'products' => true, 'organizations' => true,
                ]],
            ], [
                'identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => false,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseTransactionsRespectVoucherAllowances = [
        'data' => [
            'products' => [[
                'total_amount' => 20,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                    ['fund_id' => 2, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 10, 'limit_per_identity' => 1],
                    ['fund_id' => 2, 'limit_total' => 10, 'limit_per_identity' => 1],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 2],
                    ['amount' => 100, 'fund_id' => 2, 'limit_multiplier' => 1],
                ],
            ], [
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                    ['fund_id' => 2, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 1, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'voucher_transactions',
            'actions' => [[
                'identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [[
                    'fund_id' => 1, 'voucher' => 1,
                    'limit_available' => 2, 'products' => true, 'organizations' => true,
                ], [
                    'fund_id' => 2, 'limit_available' => 1, 'products' => true, 'organizations' => true,
                ]],
            ], [
                'identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => false,
                'assert_limits' => [[
                    'fund_id' => 1, 'voucher' => 0,
                    'limit_available' => 2, 'products' => false, 'organizations' => true,
                ]],
            ], [
                'identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [[
                    'fund_id' => 1, 'voucher' => 1,
                    'limit_available' => 1, 'products' => true, 'organizations' => true,
                ]],
            ], [
                'identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => false,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'identity' => 0, 'voucher' => 2, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                    ['fund_id' => 2, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'identity' => 0, 'voucher' => 2, 'product' => 0, 'assert_success' => false,
                'assert_limits' => [
                    ['fund_id' => 2, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 1, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'voucher_transactions',
            'actions' => [[
                'identity' => 1, 'voucher' => 3, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'identity' => 1, 'voucher' => 3, 'product' => 0, 'assert_success' => false,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseSponsorTotalLimitExceedsRemainingStock = [
        'data' => [
            'products' => [[
                'total_amount' => 5,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 5, 'limit_per_identity' => 5],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'voucher_transactions',
            'actions' => [[
                'identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'update_limits',
            'actions' => [[
                'product' => 0, 'identity' => 0, 'assert_success' => false,
                'assert_errors' => ['enable_products.0'],
                'limits' => ['fund_id' => 1, 'limit_total' => 4, 'limit_per_identity' => 1],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
            ], [
                'product' => 0, 'identity' => 0, 'assert_success' => false,
                'assert_errors' => ['enable_products.0'],
                'limits' => ['fund_id' => 1, 'limit_total' => 6, 'limit_per_identity' => 1],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseTransactionsAfterRaisingExhaustedLimits = [
        'data' => [
            'products' => [[
                'total_amount' => 20,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 2, 'limit_per_identity' => 1],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 2],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [[
                    'fund_id' => 1, 'voucher' => 1,
                    'limit_available' => 1, 'products' => true, 'organizations' => true,
                ]],
            ], [
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'voucher_transactions',
            'actions' => [[
                'identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => false,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'update_limits',
            'actions' => [[
                'product' => 0, 'identity' => 0, 'assert_success' => true,
                'limits' => ['fund_id' => 1, 'limit_total' => 5, 'limit_per_identity' => 2],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'voucher_transactions',
            'actions' => [[
                'identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [[
                    'fund_id' => 1, 'voucher' => 1,
                    'limit_available' => 2, 'products' => true, 'organizations' => true,
                ]],
            ], [
                'identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => false,
                'assert_limits' => [[
                    'fund_id' => 1, 'voucher' => 0,
                    'limit_available' => 2, 'products' => false, 'organizations' => true,
                ]],
            ], [
                'identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [[
                    'fund_id' => 1, 'voucher' => 1,
                    'limit_available' => 1, 'products' => true, 'organizations' => true,
                ]],
            ], [
                'identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'identity' => 0, 'voucher' => 1, 'product' => 0, 'assert_success' => false,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseTransactionsAfterCanceledAndRejectedReservations = [
        'data' => [
            'products' => [[
                'total_amount' => 10,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 2, 'limit_per_identity' => 1],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 2],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0, 'state' => 'canceled_by_client'],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
                'assert_limits_after' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0, 'state' => 'rejected'],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
                'assert_limits_after' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'voucher_transactions',
            'actions' => [[
                'identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ], [
                'identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'identity' => 0, 'voucher' => 0, 'product' => 0, 'assert_success' => false,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseUnlimitedPerIdentity = [
        'data' => [
            'products' => [[
                'total_amount' => 10,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [[
                    'fund_id' => 1,
                    'limit_total' => 2,
                    'limit_total_unlimited' => false,
                    'limit_per_identity' => 1,
                    'limit_per_identity_unlimited' => true,
                ]],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => null, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => null, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => null, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => false,
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseUnlimitedTotal = [
        'data' => [
            'products' => [[
                'total_amount' => 10,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [[
                    'fund_id' => 1,
                    'limit_total' => 1,
                    'limit_total_unlimited' => true,
                    'limit_per_identity' => 2,
                    'limit_per_identity_unlimited' => false,
                ]],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => false,
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseStandaloneProductVouchers = [
        'data' => [
            'products' => [[
                'total_amount' => 10,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 3, 'limit_per_identity' => 2],
                ],
            ], [
                'total_amount' => 10,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 2, 'limit_per_identity' => 3],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'product' => 1],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'product_vouchers',
            'actions' => [[
                'fund_id' => 1,
                'identity' => 0,
                'product' => 0,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ], [
                'fund_id' => 1,
                'identity' => 0,
                'product' => 1,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ], [
                'fund_id' => 1,
                'identity' => 0,
                'product' => 0,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => true, 'organizations' => true],
                ],
            ], [
                'fund_id' => 1,
                'identity' => 0,
                'product' => 1,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 1],
                'assert_success' => false,
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseSharedTotalAcrossIdentities = [
        'data' => [
            'products' => [[
                'total_amount' => 10,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 2, 'limit_per_identity' => 2],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                ],
            ], [
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 1, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 1, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 1, 'voucher' => 1, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => false,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 1, 'voucher' => 1, 'product' => 0],
                'assert_success' => false,
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseInactiveVouchers = [
        'data' => [
            'products' => [[
                'total_amount' => 20,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 10, 'limit_per_identity' => 2],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 2],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 6, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'update_vouchers',
            'actions' => [
                ['voucher' => 1, 'fields' => ['state' => Voucher::STATE_PENDING]],
            ],
        ], [
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'update_vouchers',
            'actions' => [
                ['voucher' => 1, 'fields' => ['state' => Voucher::STATE_DEACTIVATED]],
            ],
        ], [
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseExpiredVouchers = [
        'data' => [
            'products' => [[
                'total_amount' => 20,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 10, 'limit_per_identity' => 2],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 2],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 6, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'update_vouchers',
            'actions' => [
                ['voucher' => 1, 'expire_in_days' => -1],
            ],
        ], [
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 2, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 1, 'products' => true, 'organizations' => true],
                ],
            ]],
        ]],
    ];

    /**
     * @var array|array[]
     */
    protected array $testCaseLimitLoweredBelowVoucherUsage = [
        'data' => [
            'products' => [[
                'total_amount' => 20,
                'unlimited_stock' => false,
                'funds' => [
                    ['fund_id' => 1, 'allow_budget' => true, 'allow_products' => false],
                ],
                'limits' => [
                    ['fund_id' => 1, 'limit_total' => 10, 'limit_per_identity' => 3],
                ],
            ]],
            'identities' => [[
                'vouchers' => [
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                    ['amount' => 100, 'fund_id' => 1, 'limit_multiplier' => 1],
                ],
            ]],
        ],
        'tests' => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ['identity' => 0, 'product' => 0],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 6, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 5, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 4, 'products' => true, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 3, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'update_limits',
            'actions' => [[
                'product' => 0,
                'identity' => 0,
                'assert_success' => true,
                'limits' => ['fund_id' => 1, 'limit_total' => 10, 'limit_per_identity' => 1],
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => true, 'organizations' => true],
                ],
            ]],
        ], [
            'type' => 'reservations',
            'actions' => [[
                'data' => ['identity' => 0, 'voucher' => 1, 'product' => 0],
                'assert_success' => false,
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => true,
                'assert_limits' => [
                    ['fund_id' => 1, 'limit_available' => 0, 'products' => false, 'organizations' => true],
                ],
            ], [
                'data' => ['identity' => 0, 'voucher' => 0, 'product' => 0],
                'assert_success' => false,
            ]],
        ]],
    ];

    /**
     * Make one identity with 4 vouchers across 3 funds and 2 products
     * Set different shared total and individual limits per product and fund
     * Assert available quantities include voucher multipliers and product #1 has null availability in fund #3
     * Reserve products and assert the available allowance decreases
     * Assert cancellation and rejection restore allowance, while an accepted reservation remains counted
     * Exhaust product #1's allowance in fund #1 and assert another reservation is rejected
     * Change sponsor limits and assert the new available quantities account for existing usage
     * Create provider transactions until the tested vouchers reach their product allowances
     * Assert sponsor limit updates above the remaining product stock are rejected.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsCase1(): void
    {
        $this->processTestCase($this->testCase1);
    }

    /**
     * Make 2 identities with 5 vouchers across 2 funds and 2 products
     * Set different voucher multipliers and product limits for each fund
     * Assert product #2 in fund #3 has 2 available for identity #1 and 6 for identity #2
     * Reserve product #1 with identity #1 and assert its available allowance decreases
     * Reserve and cancel product #2 with identity #2, asserting its allowance falls from 6 to 5 and returns to 6
     * Raise and lower sponsor limits and assert the remaining allowances are recalculated
     * Create provider transactions with both identities and assert transactions beyond voucher allowances are rejected.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsCase2(): void
    {
        $this->processTestCase($this->testCase2);
    }

    /**
     * Make 2 identities with 5 vouchers across 2 funds and 2 products
     * Give identity #1 voucher multipliers 1 and 2 in fund #1 and 1 in fund #2
     * Give identity #2 voucher multipliers 1 in fund #1 and 2 in fund #2
     * Set product #1's shared total to 5 and individual limit to 2 in fund #1
     * Set product #1's shared total to 10 and individual limit to 3 in fund #2
     * Set product #2's shared total to 10 and individual limit to 1 in fund #1
     * Set product #2's shared total to 8 and individual limit to 2 in fund #2
     * Assert product #1 has 5 and 3 available for identity #1 in funds #1 and #2
     * Assert product #1 has 2 and 6 available for identity #2 in funds #1 and #2
     * Assert product #2 has 3 and 2 available for identity #1 in funds #1 and #2
     * Assert product #2 has 1 and 4 available for identity #2 in funds #1 and #2
     * Reserve product #1 with identity #1's multiplier-1 voucher in fund #1
     * Assert its fund #1 availability falls from 5 to 4 while fund #2 stays at 3
     * Assert product #2's availability for identity #1 stays at 3 and 2
     * Assert product #1's availability for identity #2 stays at 2 and 6.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsByIdentityFundAndMultiplier(): void
    {
        $this->processTestCase($this->testCaseAvailabilityByIdentityAndFund);
    }

    /**
     * Make one voucher and one product with general provider approval for the fund
     * Configure no product-specific approval limits
     * Assert the product has null availability and is available to the provider
     * Reserve it twice and assert availability remains null after each reservation.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsWithoutProductApproval(): void
    {
        $this->processTestCase($this->testCaseWithoutProductApprovalLimits);
    }

    /**
     * Make one voucher with multiplier 1 and a product with total and individual limits of 2
     * Reserve the product and assert availability falls from 2 to 1
     * Cancel the reservation as the requester and assert availability returns to 2
     * Reserve the product again and assert availability falls to 1.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsAfterReservationCancellation(): void
    {
        $this->processTestCase($this->testCaseCancellationRestoresAllowance);
    }

    /**
     * Make one voucher with multiplier 1 and a product with total and individual limits of 2
     * Reserve the product and assert availability falls from 2 to 1
     * Reject the reservation as the provider and assert availability returns to 2
     * Reserve the product again and assert availability falls to 1.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsAfterReservationRejection(): void
    {
        $this->processTestCase($this->testCaseRejectionRestoresAllowance);
    }

    /**
     * Make one voucher with multiplier 1 and a product with total and individual limits of 4
     * Leave one reservation pending and assert availability falls from 4 to 3
     * Create a second reservation and assert availability falls to 2
     * Accept the second reservation and assert availability stays at 2 because its usage is counted once
     * Make 2 direct transactions and assert availability falls to 1 and then 0
     * Assert another transaction is rejected and availability remains 0.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsCombineReservationsAndTransactions(): void
    {
        $this->processTestCase($this->testCaseReservationsAndTransactionsShareAllowance);
    }

    /**
     * Make 2 vouchers for the same identity in one fund and one product
     * Set voucher multipliers to 1 and 2, the shared total limit to 4 and the individual limit to 2
     * Reserve once with voucher #1 and assert availability falls from 4 to 3
     * Raise the total limit to 10, lower it to 3 and restore it to 10, asserting availability 5, 2 and 5
     * Keep the total at 10 while raising the individual limit to 3 and then lowering it to 1
     * Assert availability becomes 8 and then 2, accounting for both multipliers and the existing reservation
     * Assert unused voucher #2 still gives the provider access after voucher #1 reaches its lowered allowance
     * Assert a direct transaction with voucher #1 is rejected while 2 remain available on voucher #2
     * Make 2 transactions with voucher #2 and assert availability falls to 1 and then 0
     * Assert a third transaction is rejected at the lowered individual limit while shared allowance remains.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsAfterSponsorLimitChanges(): void
    {
        $this->processTestCase($this->testCaseSponsorLimitChangesAfterUsage);
    }

    /**
     * Make 2 identities with 4 vouchers across 2 funds and one product with individual limit 1
     * Set the product's shared total to 10 in both funds
     * Give identity #1 vouchers #1 and #2 in fund #1 with multipliers 1 and 2
     * Give identity #1 voucher #3 in fund #2 and identity #2 voucher #4 in fund #1, both with multiplier 1
     * Assert identity #1 has 3 and 1 available in funds #1 and #2, and identity #2 has 1 available in fund #1
     * Make one transaction with voucher #1 and assert fund #1 availability falls to 2 while fund #2 stays at 1
     * Assert the product remains available to the provider for unused voucher #2
     * Assert voucher #1's next transaction is rejected and availability stays at 2
     * Assert the product is unavailable to the provider for exhausted voucher #1
     * Make 2 transactions with voucher #2 and assert fund #1 availability falls to 1 and then 0
     * Assert another transaction with voucher #2 is rejected and availability stays at 0
     * Make one transaction with voucher #3 and assert fund #2 availability falls to 0 while fund #1 stays at 0
     * Assert voucher #3's next transaction is rejected and fund #2 availability stays at 0
     * Assert identity #2 still has 1 available in fund #1
     * Make one transaction with voucher #4 and assert identity #2's availability falls to 0
     * Assert voucher #4's next transaction is rejected and availability stays at 0.
     * @throws Throwable
     * @return void
     */
    public function testProductFundTransactionsRespectVoucherAllowances(): void
    {
        $this->processTestCase($this->testCaseTransactionsRespectVoucherAllowances);
    }

    /**
     * Make one voucher with multiplier 1 and a product with stock 5 and total and individual limits of 5
     * Make one reservation and assert availability falls to 4
     * Make one direct transaction and assert availability falls to 3
     * Attempt total limits of 4 and 6 with an individual limit of 1
     * Assert both updates are rejected because they exceed remaining stock, although 4 fitted the original stock
     * Assert availability stays at 3 after each rejected update.
     * @throws Throwable
     * @return void
     */
    public function testProductFundTotalLimitCannotExceedRemainingStock(): void
    {
        $this->processTestCase($this->testCaseSponsorTotalLimitExceedsRemainingStock);
    }

    /**
     * Make 2 vouchers for the same identity in one fund and one product
     * Set voucher multipliers to 1 and 2, the shared total limit to 2 and the individual limit to 1
     * Assert 2 are available, then reserve once with voucher #1 and assert availability falls to 1
     * Reserve once with voucher #2 and assert availability falls to 0
     * Assert a direct transaction with voucher #2 is rejected because the shared total is exhausted
     * Raise the total limit to 5 and the individual limit to 2 and assert 3 are available
     * Make one transaction with voucher #1 and assert availability falls to 2
     * Assert its next transaction is rejected at its new individual limit while 2 remain available on voucher #2
     * Make 2 transactions with voucher #2 and assert availability falls to 1 and then 0 at the new shared total
     * Assert another transaction is rejected even though voucher #2 still has one individual allowance remaining.
     * @throws Throwable
     * @return void
     */
    public function testProductFundTransactionsAfterRaisingExhaustedLimits(): void
    {
        $this->processTestCase($this->testCaseTransactionsAfterRaisingExhaustedLimits);
    }

    /**
     * Make one voucher with multiplier 2 and a product with total limit 2 and individual limit 1
     * Cancel one reservation as the requester and reject another as the provider
     * Assert availability falls from 2 to 1 for each reservation and returns to 2 after each state change
     * Spend the full restored allowance through 2 direct transactions, asserting availability falls to 1 and then 0
     * Assert a third transaction is rejected and availability remains 0.
     * @throws Throwable
     * @return void
     */
    public function testProductFundTransactionsAfterCanceledAndRejectedReservations(): void
    {
        $this->processTestCase($this->testCaseTransactionsAfterCanceledAndRejectedReservations);
    }

    /**
     * Make one voucher for an identity in one fund and one product
     * Set the voucher multiplier to 1, the shared total limit to 2 and the individual allowance to unlimited
     * Assert the product has null limit_available for the fund on the webshop
     * Reserve the product twice and assert limit_available remains null after each reservation
     * Assert the product remains available to the provider after the shared total limit is exhausted
     * Assert a third reservation is rejected because the shared total limit is exhausted.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsWithUnlimitedPerIdentity(): void
    {
        $this->processTestCase($this->testCaseUnlimitedPerIdentity);
    }

    /**
     * Make one voucher for an identity in one fund and one product
     * Set the voucher multiplier to 1, the shared total to unlimited and the individual allowance to 2
     * Assert the product has 2 available for the fund on the webshop
     * Reserve the product twice and assert the available allowance falls to 1 and then 0
     * Assert the product is no longer available to the provider for the voucher and a third reservation is rejected.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsWithUnlimitedTotal(): void
    {
        $this->processTestCase($this->testCaseUnlimitedTotal);
    }

    /**
     * Make one budget voucher for an identity in one fund and 2 products (1,2)
     * Set the voucher multiplier to 1
     * Set product #1 limits to 3 in total and 2 per identity
     * Set product #2 limits to 2 in total and 3 per identity
     * Make 2 standalone product vouchers for each product, without reservations or transactions
     * Assert each product's available allowance on the webshop falls from 2 to 1 to 0
     * Assert product #1 remains available to the provider for the budget voucher (its voucher allowance is unused)
     * Assert product #2 is unavailable to the provider and cannot be reserved because its shared total is exhausted.
     * @throws Throwable
     * @return void
     */
    public function testStandaloneProductVouchersConsumeProductFundLimits(): void
    {
        $this->processTestCase($this->testCaseStandaloneProductVouchers);
    }

    /**
     * Make 2 identities with one voucher each in the same fund and one product
     * Set both voucher multipliers to 1 and the product limits to 2 in total and 2 per identity
     * Assert both identities see 2 available for the fund on the webshop
     * Reserve the product with the first identity and assert both identities see 1 available
     * Reserve the product with the second identity and assert both identities see 0 available
     * Assert neither identity can reserve again, even though each still has one individual allowance remaining.
     * @throws Throwable
     * @return void
     */
    public function testProductFundTotalLimitIsSharedAcrossIdentities(): void
    {
        $this->processTestCase($this->testCaseSharedTotalAcrossIdentities);
    }

    /**
     * Make 2 vouchers for the same identity in one fund and one product
     * Set voucher multipliers to 1 and 2, the shared total limit to 10 and the individual allowance to 2
     * Assert the product has 6 available on the webshop, then 5 after reserving with voucher #2
     * Change voucher #2 to pending and then deactivated, asserting 5 remains available after each change
     * Assert its multiplier and previous usage still count while it is unexpired
     * Reserve with active voucher #1 and assert the available allowance falls to 4.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsIncludeInactiveVouchers(): void
    {
        $this->processTestCase($this->testCaseInactiveVouchers);
    }

    /**
     * Make 2 vouchers for the same identity in one fund and one product
     * Set voucher multipliers to 1 and 2, the shared total limit to 10 and the individual allowance to 2
     * Assert the product has 6 available on the webshop, then 5 after reserving with voucher #2
     * Expire voucher #2 while keeping the fund and voucher #1 unexpired
     * Assert 2 remains available because both the expired voucher's multiplier and usage are excluded
     * Reserve with voucher #1 and assert the available allowance falls to 1.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsExcludeExpiredVouchers(): void
    {
        $this->processTestCase($this->testCaseExpiredVouchers);
    }

    /**
     * Make 2 vouchers for the same identity in one fund and one product
     * Set both voucher multipliers to 1, the shared total limit to 10 and the individual allowance to 3
     * Reserve the product 3 times with voucher #2, reducing the aggregate allowance from 6 to 3
     * Lower the individual allowance to 1 and assert the aggregate remainder of 2 minus 3 is clamped to 0
     * Assert the product remains available to the provider for unused voucher #1 despite the aggregate allowance of 0
     * Assert voucher #2 cannot reserve again, while unused voucher #1 can still reserve once
     * Assert the product becomes unavailable to the provider after voucher #1 reserves once
     * Assert the aggregate allowance stays at 0 and voucher #1 cannot reserve a second time.
     * @throws Throwable
     * @return void
     */
    public function testProductFundLimitsWhenLimitIsLoweredBelowVoucherUsage(): void
    {
        $this->processTestCase($this->testCaseLimitLoweredBelowVoucherUsage);
    }

    /**
     * @param array $testCase
     * @throws Throwable
     * @return void
     */
    protected function processTestCase(array $testCase): void
    {
        $configs = $this->setConfigs();

        $this->makeTestData($testCase['data']);
        $this->processCaseTests($testCase['tests']);
        $this->rollbackConfigs($configs);
    }

    /**
     * @param array $testData
     * @throws Throwable
     * @return void
     */
    protected function makeTestData(array $testData): void
    {
        $this->makeProducts($testData['products']);
        $this->makeIdentities($testData['identities']);
    }

    /**
     * @return array
     */
    protected function setConfigs(): array
    {
        $initialValue = [
            'forus.transactions.soft_limit' => Config::get('forus.transactions.soft_limit'),
            'forus.transactions.hard_limit' => Config::get('forus.transactions.hard_limit'),
        ];

        Cache::clear();
        Config::set('forus.transactions.soft_limit', 0);
        Config::set('forus.transactions.hard_limit', 0);

        return $initialValue;
    }

    /**
     * @param array $configs
     * @return void
     */
    protected function rollbackConfigs(array $configs): void
    {
        foreach ($configs as $key => $value) {
            Config::set($key, $value);
        }
    }

    /**
     * @param array $identitiesData
     * @return void
     */
    protected function makeIdentities(array $identitiesData): void
    {
        foreach ($identitiesData as $identityData) {
            $identity = $this->makeIdentity($this->makeUniqueEmail());
            $this->identities[] = $identity;

            // make vouchers
            foreach ($identityData['vouchers'] as $voucherArr) {
                $fund = $this->findFund($voucherArr['fund_id']);
                $limit = $voucherArr['limit_multiplier'];

                $voucher = $this->makeTestVoucher(
                    $fund,
                    $identity,
                    amount: $voucherArr['amount'],
                    limitMultiplier: $limit,
                );

                $this->assertNotNull($voucher, 'Voucher not found');
                $this->vouchers[] = $voucher;
            }
        }
    }

    /**
     * @param array $productsData
     * @throws Throwable
     * @return void
     */
    protected function makeProducts(array $productsData): void
    {
        $testData = new TestData();

        foreach ($productsData as $productData) {
            $organization = $testData->makeOrganization('Provider I', $this->makeIdentity());

            $product = $testData->makeProduct(
                $organization,
                Arr::only($productData, ['total_amount', 'unlimited_stock']),
            );

            foreach ($productData['funds'] as $fundData) {
                $fund = $this->findFund($fundData['fund_id']);
                $limits = array_filter($productData['limits'], fn ($limit) => $limit['fund_id'] === $fund->id);

                /** @var FundProvider $fundProvider */
                $fundProvider = $fund->providers()->updateOrCreate([
                    'organization_id' => $organization->id,
                ], [
                    'state' => FundProvider::STATE_ACCEPTED,
                    'allow_budget' => Arr::get($fundData, 'allow_budget', true),
                    'allow_products' => Arr::get($fundData, 'allow_products', false),
                ]);

                $this->updateProvider($fund, $fundProvider, [
                    'enable_products' => array_map(fn ($productLimit) => [
                        'id' => $product->id,
                        'payment_type' => 'budget',
                        'limit_total' => Arr::get($productLimit, 'limit_total'),
                        'limit_per_identity' => Arr::get($productLimit, 'limit_per_identity'),
                        ...Arr::only($productLimit, ['limit_total_unlimited', 'limit_per_identity_unlimited']),
                    ], $limits),
                ]);
            }

            $this->products[] = $product;
        }
    }

    /**
     * @param array $tests
     * @throws Throwable
     * @return void
     */
    protected function processCaseTests(array $tests): void
    {
        foreach ($tests as $test) {
            match ($test['type']) {
                'assertion' => $this->processActionAssertion($test['actions']),
                'reservations' => $this->processActionReservations($test['actions']),
                'update_limits' => $this->processActionUpdateLimits($test['actions']),
                'update_vouchers' => $this->processActionUpdateVouchers($test['actions']),
                'voucher_transactions' => $this->processVoucherTransactions($test['actions']),
                'product_vouchers' => $this->processProductVouchers($test['actions']),
                default => null,
            };
        }
    }

    /**
     * @param array $actions
     * @return void
     */
    protected function processProductVouchers(array $actions): void
    {
        foreach ($actions as $action) {
            $fund = $this->findFund($action['fund_id']);

            $product = $this->products[$action['product']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            $identity = $this->identities[$action['identity']] ?? null;
            $this->assertNotNull($identity, 'Identity not found');

            $productVoucher = $this->makeTestProductVoucher($fund, $identity, [], $product->id);
            $this->assertNotNull($productVoucher, 'Product voucher not created');

            $this->assertProductLimits($identity, $product, $action['assert_limits']);
        }
    }

    /**
     * @param array $actions
     * @return void
     */
    protected function processVoucherTransactions(array $actions): void
    {
        foreach ($actions as $action) {
            $product = $this->products[$action['product']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            $voucher = $this->vouchers[$action['voucher']] ?? null;
            $this->assertNotNull($voucher, 'Voucher not found');

            $this->makeTransaction($product->organization, $voucher, $product, $action['assert_success']);

            if ($action['assert_limits'] ?? false) {
                $identity = $this->identities[$action['identity']] ?? null;
                $this->assertNotNull($identity, 'Identity not found');
                $this->assertProductLimits($identity, $product, $action['assert_limits']);
            }

            usleep(10 * 1000);
        }
    }

    /**
     * @param Organization $provider
     * @param Voucher $voucher
     * @param Product $product
     * @param bool $assertCreated
     * @return void
     */
    protected function makeTransaction(
        Organization $provider,
        Voucher $voucher,
        Product $product,
        bool $assertCreated = true
    ): void {
        $response = $this->makeProviderVoucherTransactionRequest($voucher, $provider, [
            'product_id' => $product->id,
        ], $provider->identity);

        if ($assertCreated) {
            $response->assertSuccessful();

            $transaction = VoucherTransaction::find($response->json('data.id'));
            $this->assertNotNull($transaction, 'Voucher transaction not found');
        } else {
            $response->assertJsonValidationErrorFor('product_id');
        }
    }

    /**
     * @param array $actions
     * @throws Throwable
     * @return void
     */
    protected function processActionAssertion(array $actions): void
    {
        foreach ($actions as $action) {
            $product = $this->products[$action['data']['product']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            $identity = $this->identities[$action['data']['identity']] ?? null;
            $this->assertNotNull($identity, 'Identity not found');

            $this->assertProductLimits($identity, $product, $action['assert_limits']);
        }
    }

    /**
     * @param array $actions
     * @throws Throwable
     * @return void
     */
    protected function processActionReservations(array $actions): void
    {
        foreach ($actions as $action) {
            $product = $this->products[$action['data']['product']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            $provider = $product->organization;
            $identity = $this->identities[$action['data']['identity']] ?? null;
            $this->assertNotNull($identity, 'Identity not found');

            $voucher = $this->vouchers[$action['data']['voucher']] ?? null;
            $this->assertNotNull($voucher, 'Voucher not found');

            $reservation = $this->makeProductReservation($voucher, $product, $action['assert_success']);

            if ($action['assert_limits'] ?? false) {
                $this->assertProductLimits($identity, $product, $action['assert_limits']);
            }

            if ($reservation && ($action['data']['state'] ?? false)) {
                $this->changeReservationState($provider, $reservation, $action['data']['state']);
            }

            if ($action['assert_limits_after'] ?? false) {
                $this->assertProductLimits($identity, $product, $action['assert_limits_after']);
            }
        }
    }

    /**
     * @param array $actions
     * @return void
     */
    protected function processActionUpdateLimits(array $actions): void
    {
        foreach ($actions as $action) {
            $limits = $action['limits'];
            $fund = $this->findFund($limits['fund_id']);

            $product = $this->products[$action['product']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            /** @var FundProvider $fundProvider */
            $fundProvider = $product->organization->fund_providers()->where('fund_id', $fund->id)->first();
            $this->assertNotNull($fundProvider, 'Fund Provider not found');

            $this->updateProvider($fund, $fundProvider, [
                'enable_products' => [[
                    'id' => $product->id,
                    'payment_type' => 'budget',
                    'limit_total' => $limits['limit_total'],
                    'limit_per_identity' => $limits['limit_per_identity'],
                ]],
            ], $action['assert_success'] ? null : ($action['assert_errors'] ?? []));

            if ($action['assert_limits'] ?? false) {
                $identity = $this->identities[$action['identity']] ?? null;
                $this->assertNotNull($identity, 'Identity not found');
                $this->assertProductLimits($identity, $product, $action['assert_limits']);
            }
        }
    }

    /**
     * @param array $actions
     * @return void
     */
    protected function processActionUpdateVouchers(array $actions): void
    {
        foreach ($actions as $action) {
            $voucher = $this->vouchers[$action['voucher']] ?? null;
            $this->assertNotNull($voucher, 'Voucher not found');

            $fields = $action['fields'] ?? [];

            if (isset($action['expire_in_days'])) {
                $fields['expire_at'] = today()->addDays($action['expire_in_days']);
            }

            $voucher->update($fields);
        }
    }

    /**
     * @param Organization $provider
     * @param ProductReservation $reservation
     * @param string $state
     * @return void
     */
    protected function changeReservationState(
        Organization $provider,
        ProductReservation $reservation,
        string $state
    ): void {
        if ($reservation->state === $state) {
            return;
        }

        match ($state) {
            ProductReservation::STATE_REJECTED,
            ProductReservation::STATE_CANCELED_BY_PROVIDER => $this->cancelReservationByProvider(
                $provider,
                $reservation
            ),
            ProductReservation::STATE_ACCEPTED => $this->approveReservationByProvider($provider, $reservation),
            ProductReservation::STATE_CANCELED_BY_CLIENT => (
                $this->makeReservationCancelRequest($reservation)->assertSuccessful()
            ),
            default => $reservation
        };

        $reservation = ProductReservation::find($reservation->id);

        $this->assertSame($reservation->state, $state, "Reservation state $reservation->state not equals $state");
    }

    /**
     * @param Organization $provider
     * @param ProductReservation $reservation
     * @return void
     */
    protected function cancelReservationByProvider(Organization $provider, ProductReservation $reservation): void
    {
        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);
        $url = "/api/v1/platform/organizations/$provider->id/product-reservations/$reservation->id/reject";
        $response = $this->postJson($url, [], $headers);

        $response->assertSuccessful();
    }

    /**
     * @param Organization $provider
     * @param ProductReservation $reservation
     * @return void
     */
    protected function approveReservationByProvider(Organization $provider, ProductReservation $reservation): void
    {
        $this->postJson(
            "/api/v1/platform/organizations/$provider->id/product-reservations/$reservation->id/accept",
            [],
            $this->makeApiHeaders($this->makeIdentityProxy($provider->identity)),
        )->assertSuccessful();
    }

    /**
     * @param Identity $identity
     * @param Product $product
     * @param array $asserts
     * @return void
     */
    protected function assertProductLimits(Identity $identity, Product $product, array $asserts): void
    {
        $provider = $product->organization;
        $productFunds = $this->getProductOnWebshop($product, $identity)['funds'];

        foreach ($asserts as $assert) {
            $fund = Arr::first(Arr::where($productFunds, fn ($item) => $assert['fund_id'] === $item['id']));

            $this->assertNotNull($fund, 'Fund not found');
            $this->assertSame($assert['limit_available'], $fund['limit_available'], 'Limits not equals');

            $vouchers = array_filter($this->vouchers, fn (Voucher $item, int $index) => (
                $assert['fund_id'] === $item->fund_id &&
                $identity->id === $item->identity_id &&
                (!isset($assert['voucher']) || $assert['voucher'] === $index)
            ), ARRAY_FILTER_USE_BOTH);

            $this->assertNotEmpty($vouchers, 'Vouchers not found');

            $exists = null;

            foreach ($vouchers as $voucher) {
                if ($exists) {
                    continue;
                }

                $this->checkAllowedOrganizationsForProvider($provider, $voucher, $assert['organizations']);
                $response = $this->getProductsForProviderRequest($provider, $voucher);

                if ($assert['products']) {
                    $response->assertSuccessful();
                    $exists = Arr::first(Arr::where($response['data'], fn ($item) => $product->id === $item['id']));
                } else {
                    $response->assertForbidden();
                }
            }

            $assert['products']
                ? $this->assertNotNull($exists, 'The product must be available to the provider.')
                : $this->assertNull($exists, 'The product must not be available to the provider.');
        }
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @param bool $assertCreated
     * @return ProductReservation|null
     */
    protected function makeProductReservation(
        Voucher $voucher,
        Product $product,
        bool $assertCreated = true
    ): ?ProductReservation {
        $response = $this->makeReservationStoreRequest($voucher, $product);

        if ($assertCreated) {
            $response->assertSuccessful();

            $reservation = ProductReservation::find($response->json('data.id'));
            $this->assertNotNull($reservation, 'Reservation not found');

            return $reservation;
        }

        $response->assertJsonValidationErrorFor('product_id');

        return null;
    }

    /**
     * @param Product $product
     * @param Identity $identity
     * @return Collection|TestResponse|array
     */
    protected function getProductOnWebshop(Product $product, Identity $identity): Collection|TestResponse|array
    {
        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->getJson("/api/v1/platform/products?organization_id=$product->organization_id", $headers);
        $response->assertSuccessful();

        $productArr = Arr::first(Arr::where($response['data'], fn ($item) => $product->id === $item['id']));
        $this->assertNotNull($productArr, 'Product not found');

        return $productArr;
    }

    /**
     * @param Organization $providerOrganization
     * @param Voucher $voucher
     * @return Collection|TestResponse|array
     */
    protected function getProductsForProviderRequest(
        Organization $providerOrganization,
        Voucher $voucher,
    ): Collection|TestResponse|array {
        $proxy = $this->makeIdentityProxy($providerOrganization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $voucherToken = $voucher->token_without_confirmation->address;
        $url = "/api/v1/platform/provider/vouchers/$voucherToken/products?organization_id=$providerOrganization->id";

        return $this->getJson($url, $headers);
    }

    /**
     * @param Organization $provider
     * @param Voucher $voucher
     * @param bool $exist
     * @return Collection|TestResponse|array
     */
    protected function checkAllowedOrganizationsForProvider(
        Organization $provider,
        Voucher $voucher,
        bool $exist = true
    ): Collection|TestResponse|array {
        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);

        $voucherToken = $voucher->token_without_confirmation->address;
        $response = $this->getJson("/api/v1/platform/provider/vouchers/$voucherToken", $headers);
        $response->assertSuccessful();

        $organizationExists = Arr::first(Arr::where(
            $response['data']['allowed_organizations'],
            fn ($item) => $provider->id === $item['id']
        ));

        $exist
            ? $this->assertNotNull($organizationExists, 'The provider organization should be in the list.')
            : $this->assertNull($organizationExists, 'The provider organization must not be in the list.');

        return $response;
    }

    /**
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param array $params
     * @param array|null $assertErrors
     * @return void
     */
    protected function updateProvider(
        Fund $fund,
        FundProvider $fundProvider,
        array $params,
        ?array $assertErrors = null,
    ): void {
        $proxy = $this->makeIdentityProxy($fund->organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $url = "/api/v1/platform/organizations/$fund->organization_id/funds/$fund->id/providers/$fundProvider->id";
        $response = $this->patch($url, $params, $headers);

        if (is_null($assertErrors)) {
            $response->assertSuccessful();
        } else {
            $response->assertJsonValidationErrors($assertErrors);
        }
    }

    /**
     * @param $fundId
     * @return Fund
     */
    protected function findFund($fundId): Fund
    {
        /** @var Fund $fund */
        $fund = FundQuery::whereActiveFilter(Fund::where('id', $fundId))->first();
        $this->assertNotNull($fund, 'Fund not found');

        return $fund;
    }
}
