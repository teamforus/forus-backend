<?php

namespace Tests\Unit;

use App\Helpers\Validation;
use App\Rules\BsnRule;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;
use Tests\TestCase;

class BsnRuleTest extends TestCase
{
    use DoesTesting, DatabaseTransactions, CreatesApplication;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testBsnRule(): void
    {
        $this->assertTrue(Validation::check('test', [new BsnRule()])->fails());
        $this->assertTrue(Validation::check('12345', [new BsnRule()])->fails());
        $this->assertTrue(Validation::check(null, [new BsnRule()])->fails());
        $this->assertTrue(Validation::check('6058012900', [new BsnRule()])->fails());
        $this->assertTrue(Validation::check('6058012', [new BsnRule()])->fails());
        $this->assertTrue(Validation::check('', ['required', new BsnRule()])->fails());

        $this->assertTrue(Validation::check('605801290', [new BsnRule()])->passes());
        $this->assertTrue(Validation::check('60580129', [new BsnRule()])->passes());
        $this->assertTrue(Validation::check('', [new BsnRule()])->passes());
    }
}
