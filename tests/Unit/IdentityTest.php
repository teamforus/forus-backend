<?php

namespace Tests\Unit;

use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;
use Tests\TestCase;

class IdentityTest extends TestCase
{
    use DoesTesting, DatabaseTransactions, CreatesApplication;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testUserEmails(): void
    {
        // create user with email
        $identity = $this->makeIdentity('user1@example.com');

        // check that the email is set as primary and user has only one email
        $this->assertCount(1, $identity->emails);
        $this->assertEquals('user1@example.com', $identity->email);

        // add 2 more emails and set the latter as primary
        $identity->addEmail('user2@example.com');
        $identity->addEmail('user3@example.com')->setPrimary();

        // check that user has now 3 emails and the primary emails has changed
        $identity->unsetRelations();
        $this->assertCount(3, $identity->emails);
        $this->assertEquals('user3@example.com', $identity->email);
    }
}
