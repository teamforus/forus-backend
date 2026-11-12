<?php

namespace App\Services\IdentityBsnService;

use App\Models\Identity;
use App\Models\Record;
use App\Models\RecordType;
use App\Services\IdentityBsnService\Exceptions\IdentityBsnException;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Throwable;

class IdentityBsnService
{
    /**
     * @param string $bsn
     * @return string
     */
    public function normalizeBsn(string $bsn): string
    {
        return str_pad(trim($bsn), 9, '0', STR_PAD_LEFT);
    }

    /**
     * @template T
     * @param string $bsn
     * @param Closure(): T $callback
     * @throws Throwable
     * @return T
     */
    public function transactionWithBsnLock(string $bsn, Closure $callback): mixed
    {
        return DB::transaction(function () use ($bsn, $callback) {
            $bsn = $this->normalizeBsn($bsn);

            // The no-op update holds an exclusive lock until the outer transaction ends.
            DB::table('identity_bsn_locks')->upsert([['bsn' => $bsn]], ['bsn'], ['bsn']);

            return $callback();
        }, 3);
    }

    /**
     * @param string $bsn
     * @param bool $lock
     * @return Identity|null
     */
    public function findIdentityByBsn(string $bsn, bool $lock = false): ?Identity
    {
        $records = Record::query()
            ->where('value', $this->normalizeBsn($bsn))
            ->where('record_type_id', RecordType::findByKey(RecordType::KEY_BSN)?->id);

        if ($lock) {
            $records->lockForUpdate();
        }

        $identities = Identity::query()->whereIn('address', $records->pluck('identity_address')->unique());

        if ($lock) {
            $identities->lockForUpdate();
        }

        $matches = $identities->limit(2)->get();

        if ($matches->count() > 1) {
            throw new IdentityBsnException(IdentityBsnException::AMBIGUOUS);
        }

        return $matches->first();
    }

    /**
     * Return distinct matches keyed by the supplied BSNs, including empty and ambiguous groups.
     *
     * @param string[] $bsns
     * @return SupportCollection<string, Collection<int, Identity>>
     */
    public function findIdentityMatchesByBsns(array $bsns): SupportCollection
    {
        if ($bsns === []) {
            return collect();
        }

        $normalized = collect($bsns)->mapWithKeys(fn (string $bsn) => [$bsn => $this->normalizeBsn($bsn)]);

        $records = Record::query()
            ->whereIn('value', $normalized->unique())
            ->where('record_type_id', RecordType::findByKey(RecordType::KEY_BSN)?->id)
            ->with('identity')->distinct()->get(['value', 'identity_address']);

        $matches = $records->groupBy('value')->map(fn (Collection $records) => new Collection(
            $records->pluck('identity')->filter()->unique('id')->values()->all(),
        ));

        return $normalized->map(fn (string $bsn) => $matches->get($bsn, new Collection()));
    }

    /**
     * @param string $bsn
     * @return Identity|null
     */
    public function findIdentityByBsnForUpdate(string $bsn): ?Identity
    {
        return $this->findIdentityByBsn($bsn, true);
    }

    /**
     * @param Identity $identity
     * @param string $bsn
     * @throws Throwable
     * @return Record
     */
    public function assignBsn(Identity $identity, string $bsn): Record
    {
        $bsn = $this->normalizeBsn($bsn);

        return $this->transactionWithBsnLock($bsn, function () use ($identity, $bsn) {
            $matched = $this->findIdentityByBsnForUpdate($bsn);

            if ($matched && $matched->id !== $identity->id) {
                throw new IdentityBsnException(IdentityBsnException::ALREADY_ASSIGNED);
            }

            $lockedIdentity = $matched ?? Identity::query()->lockForUpdate()->findOrFail($identity->id);
            $recordType = RecordType::query()->where('key', RecordType::KEY_BSN)->firstOrFail();
            $record = $lockedIdentity->records()->where('record_type_id', $recordType->id)->lockForUpdate()->first();

            if ($record && $record->value !== $bsn) {
                abort(403, 'record.exceptions.bsn_record_cant_be_changed');
            }

            $record = $lockedIdentity->records()->create([
                'order' => 0,
                'value' => $bsn,
                'record_type_id' => $recordType->id,
                'record_category_id' => null,
            ]);

            $identity->load('record_bsn');

            return $record;
        });
    }
}
