<?php

namespace Tests\Feature;

use App\Models\RecordType;
use App\Services\IdentityBsnService\Exceptions\IdentityBsnException;
use App\Services\IdentityBsnService\IdentityBsnService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Throwable;

class IdentityBsnServiceTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @throws Throwable
     * @return void
     */
    public function testAssignsAndFindsCanonicalBsn(): void
    {
        $service = resolve(IdentityBsnService::class);
        $bsn = $this->makeUniqueBsn(leadingZero: true);
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $record = $service->assignBsn($identity, substr($bsn, 1));

        $this->assertSame($bsn, $record->value);
        $this->assertSame($bsn, $identity->refresh()->bsn);
        $this->assertSame($identity->id, $service->findIdentityByBsn($bsn)?->id);
        $this->assertSame($identity->id, $service->findIdentityByBsn(substr($bsn, 1))?->id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testBatchLookupReturnsDistinctIdentityMatchesForEachInput(): void
    {
        $service = resolve(IdentityBsnService::class);
        $bsn = $this->makeUniqueBsn(leadingZero: true);
        $missingBsn = $this->makeUniqueBsn();
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $bsn);

        $identity->setBsnRecord($bsn);
        $matches = $service->findIdentityMatchesByBsns([$bsn, substr($bsn, 1), $missingBsn]);

        $this->assertCount(3, $matches);
        $this->assertSame([$identity->id], $matches->get($bsn)->modelKeys());
        $this->assertSame([$identity->id], $matches->get(substr($bsn, 1))->modelKeys());
        $this->assertTrue($matches->get($missingBsn)->isEmpty());
        $this->assertSame($identity->id, $service->findIdentityByBsn($bsn)?->id);
        $this->assertNull($service->findIdentityByBsn($missingBsn));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsAmbiguousIdentityOwnership(): void
    {
        $service = resolve(IdentityBsnService::class);
        $bsn = $this->makeUniqueBsn();
        $owner = $this->makeIdentity($this->makeUniqueEmail(), $bsn);
        $other = $this->makeIdentity($this->makeUniqueEmail());

        $other->records()->create([
            'record_type_id' => $owner->record_bsn->record_type_id,
            'value' => $bsn,
        ]);

        $this->assertEqualsCanonicalizing(
            [$owner->id, $other->id],
            $service->findIdentityMatchesByBsns([$bsn])->get($bsn)->modelKeys(),
        );

        try {
            $service->findIdentityByBsn($bsn);
            $this->fail('Ambiguous BSN ownership should be rejected.');
        } catch (IdentityBsnException $exception) {
            $this->assertSame(IdentityBsnException::AMBIGUOUS, $exception->errorCode);
        }

        $this->assertSame($bsn, $owner->refresh()->bsn);
        $this->assertSame($bsn, $other->refresh()->bsn);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsBsnAssignedToAnotherIdentity(): void
    {
        $service = resolve(IdentityBsnService::class);
        $bsn = $this->makeUniqueBsn();
        $owner = $this->makeIdentity($this->makeUniqueEmail(), $bsn);
        $target = $this->makeIdentity($this->makeUniqueEmail());

        try {
            $service->assignBsn($target, $bsn);
            $this->fail('A BSN owned by another identity should be rejected.');
        } catch (IdentityBsnException $exception) {
            $this->assertSame(IdentityBsnException::ALREADY_ASSIGNED, $exception->errorCode);
        }

        $this->assertSame($owner->id, $service->findIdentityByBsn($bsn)?->id);
        $this->assertSame($bsn, $owner->refresh()->bsn);
        $this->assertNull($target->refresh()->bsn);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsChangingAnIdentityBsn(): void
    {
        $service = resolve(IdentityBsnService::class);
        $bsn = $this->makeUniqueBsn();
        $replacement = $this->makeUniqueBsn();
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $bsn);

        try {
            $service->assignBsn($identity, $replacement);
            $this->fail('Changing an identity BSN should be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame($bsn, $identity->refresh()->bsn);
        $this->assertNull($service->findIdentityByBsn($replacement));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRollsBackIdentityCreationAndAssignmentOnFailure(): void
    {
        $service = resolve(IdentityBsnService::class);
        $bsn = $this->makeUniqueBsn();
        $identityId = null;
        $recordId = null;

        try {
            $service->transactionWithBsnLock($bsn, function () use ($service, $bsn, &$identityId, &$recordId) {
                $identity = $this->makeIdentity($this->makeUniqueEmail());
                $identityId = $identity->id;
                $recordId = $service->assignBsn($identity, $bsn)->id;

                throw new RuntimeException('Fail after BSN assignment.');
            });

            $this->fail('The injected failure should escape the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fail after BSN assignment.', $exception->getMessage());
        }

        $this->assertNotNull($identityId);
        $this->assertNotNull($recordId);
        $this->assertDatabaseMissing('identities', ['id' => $identityId]);
        $this->assertDatabaseMissing('records', ['id' => $recordId]);
        $this->assertNull($service->findIdentityByBsn($bsn));

        $identity = $this->makeIdentity($this->makeUniqueEmail(), $bsn);

        $this->assertSame($identity->id, $service->findIdentityByBsn($bsn)?->id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAddRecordsStoresBsnAndOrdinaryRecords(): void
    {
        $bsn = $this->makeUniqueBsn(leadingZero: true);
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $type = RecordType::create([
            'key' => 'test_' . token_generator()->generate(16),
            'type' => RecordType::TYPE_STRING,
        ]);

        $records = $identity->addRecords(['bsn' => substr($bsn, 1), $type->key => 'Test value']);

        $this->assertCount(2, $records);
        $this->assertSame($bsn, $identity->refresh()->bsn);
        $this->assertSame('Test value', $identity->records()->where('record_type_id', $type->id)->value('value'));
        $this->assertEqualsCanonicalizing([$bsn, 'Test value'], $records->pluck('value')->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAddRecordsCannotBypassBsnOwnership(): void
    {
        $bsn = $this->makeUniqueBsn();
        $owner = $this->makeIdentity($this->makeUniqueEmail(), $bsn);
        $target = $this->makeIdentity($this->makeUniqueEmail());

        $type = RecordType::create([
            'key' => 'test_' . token_generator()->generate(16),
            'type' => RecordType::TYPE_STRING,
        ]);

        try {
            $target->addRecords(['bsn' => $bsn, $type->key => 'Must not be saved']);
            $this->fail('Adding records must preserve BSN ownership.');
        } catch (IdentityBsnException $exception) {
            $this->assertSame(IdentityBsnException::ALREADY_ASSIGNED, $exception->errorCode);
        }

        $this->assertSame($bsn, $owner->refresh()->bsn);
        $this->assertNull($target->refresh()->bsn);
        $this->assertFalse($target->records()->where('record_type_id', $type->id)->exists());
    }
}
