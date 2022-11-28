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
        // prepare 3 emails
        $emails = array_map(fn () => $this->makeUniqueEmail(), range(1, 3));

        // create user with email
        $identity = $this->makeIdentity($emails[0]);

        // check that the email is set as primary and user has only one email
        $this->assertCount(1, $identity->emails);
        $this->assertEquals($emails[0], $identity->email);

        // add 2 more emails and set the latter as primary
        $identity->addEmail($emails[1]);
        $identity->addEmail($emails[2])->setPrimary();

        // check that user has now 3 emails and the primary emails has changed
        $identity->unsetRelations();
        $this->assertCount(3, $identity->emails);
        $this->assertEquals($emails[2], $identity->email);
    }
}
