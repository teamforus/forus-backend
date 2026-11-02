<?php

namespace App\Services\IdentityBsnService;

use App\Models\Identity;
use App\Models\Record;
use App\Models\RecordType;
use App\Services\IdentityBsnService\Exceptions\IdentityBsnException;
use Closure;
use Illuminate\Support\Facades\DB;

class IdentityBsnService
{
    /**
     * @param string $bsn
     * @return string
     */
    public function normalize(string $bsn): string
    {
        return str_pad(trim($bsn), 9, '0', STR_PAD_LEFT);
    }

    /**
     * @template T
     * @param string $bsn
     * @param Closure(): T $callback
     * @return T
     */
    public function transaction(string $bsn, Closure $callback): mixed
    {
        return DB::transaction(function () use ($bsn, $callback) {
            $bsn = $this->normalize($bsn);

            // The no-op update holds an exclusive lock until the outer transaction ends.
            DB::table('identity_bsn_locks')->upsert([['bsn' => $bsn]], ['bsn'], ['bsn']);

            return $callback();
        });
    }

    /**
     * @param string $bsn
     * @param bool $lock
     * @return Identity|null
     */
    public function find(string $bsn, bool $lock = false): ?Identity
    {
        $bsn = $this->normalize($bsn);
        $values = str_starts_with($bsn, '0') ? [$bsn, substr($bsn, 1)] : [$bsn];
        $records = Record::query()->whereIn('value', $values)
            ->where('record_type_id', RecordType::findByKey('bsn')?->id);

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
     * @param Identity $identity
     * @param string $bsn
     * @return Record
     */
    public function assign(Identity $identity, string $bsn): Record
    {
        $bsn = $this->normalize($bsn);

        return $this->transaction($bsn, function () use ($identity, $bsn) {
            $matched = $this->find($bsn, true);

            if ($matched && $matched->id !== $identity->id) {
                throw new IdentityBsnException(IdentityBsnException::ALREADY_ASSIGNED);
            }

            $lockedIdentity = Identity::query()->lockForUpdate()->findOrFail($identity->id);
            $recordType = RecordType::query()->where('key', 'bsn')->firstOrFail();
            $record = $lockedIdentity->records()->where('record_type_id', $recordType->id)->lockForUpdate()->first();

            if ($record && $this->normalize($record->value) !== $bsn) {
                abort(403, 'record.exceptions.bsn_record_cant_be_changed');
            }

            $record ??= $lockedIdentity->records()->create([
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
