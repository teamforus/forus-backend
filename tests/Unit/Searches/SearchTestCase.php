<?php

namespace Tests\Unit\Searches;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

abstract class SearchTestCase extends TestCase
{
    use DatabaseTransactions;

    /**
     * @param Builder|Relation $builder
     * @return void
     */
    protected function assertQueryBuilds(Builder|Relation $builder): void
    {
        $this->assertIsString($builder->toSql());

        $this->assertNoException(
            fn () => $builder->limit(1)->get(),
            'Search query should execute without exceptions.'
        );
    }
}
