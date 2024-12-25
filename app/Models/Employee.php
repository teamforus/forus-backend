<?php

namespace App\Models;

use App\Helpers\Arr;
use App\Services\EventLogService\Models\EventLog;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;

/**
 * App\Models\Employee
 *
 * @property int $id
 * @property string $identity_address
 * @property int $organization_id
 * @property int|null $office_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestRecord[] $fund_request_records
 * @property-read int|null $fund_request_records_count
 * @property-read \App\Models\Identity|null $identity
 * @property-read \Illuminate\Database\Eloquent\Collection|EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Office|null $office
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereOfficeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withoutTrashed()
 * @mixin \Eloquent
 */
class Employee extends BaseModel
{
    use HasLogs;
    use SoftDeletes;

    public const string EVENT_CREATED = 'created';
    public const string EVENT_UPDATED = 'updated';
    public const string EVENT_DELETED = 'deleted';

    public const string EVENT_UPLOADED_PAYOUTS = 'uploaded_payouts';
    public const string EVENT_UPLOADED_VOUCHERS = 'uploaded_vouchers';
    public const string EVENT_UPLOADED_TRANSACTIONS = 'uploaded_transactions';
    public const string EVENT_UPLOADED_PREVALIDATIONS = 'uploaded_prevalidations';

    public const string EVENT_FUND_REQUEST_ASSIGNED = 'fund_request_assigned';

    protected $fillable = [
        'identity_address', 'organization_id', 'office_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, (new EmployeeRole)->getTable());
    }

    /**
     * @return HasMany
     */
    public function fund_request_records(): HasMany
    {
        return $this->hasMany(FundRequestRecord::class);
    }

    /**
     * @param array $fileData
     * @param array $itemsData
     * @param string $type
     * @return array
     */
    protected function storeUploadedCsvFile(array $fileData, array $itemsData, string $type): array
    {
        $file = tmpfile();

        $meta = [
            'total' => Arr::get($fileData, 'total'),
            'chunk' => Arr::get($fileData, 'chunk'),
            'chunks' => Arr::get($fileData, 'chunks'),
            'chunkSize' => Arr::get($fileData, 'chunkSize'),
        ];

        fwrite($file, json_pretty([
            'name' => Arr::get($fileData, 'name'),
            'content' => Arr::get($fileData, 'content'),
            ...$meta,
            'data' => $itemsData,
        ]));

        $filePath = stream_get_meta_data($file)['uri'];
        $fileName = token_generator()->generate(32) . '.json';
        $uploadedFile = new UploadedFile($filePath, $fileName, 'application/json');

        $fileModel = resolve('file')->uploadSingle($uploadedFile, $type, [
            'storage_prefix' => '/uploaded_csv_details',
        ]);

        fclose($file);

        $fileModel->update([
            'fileable_id' => $this->id,
            'fileable_type' => $this->getMorphClass(),
            'identity_address' => $this->identity_address,
        ]);

        return [
            ...$meta,
            'file_id' => $fileModel->id,
        ];
    }

    /**
     * @param string $event
     * @param ?array $fileData
     * @param array $itemsData
     * @return EventLog
     */
    public function logCsvUpload(
        string $event,
        ?array $fileData = null,
        array $itemsData = [],
    ): EventLog {
        $fileMeta = $fileData ? $this->storeUploadedCsvFile($fileData, $itemsData, $event) : [];

        return $this->log($event, [
            'employee' => $this,
        ],  $fileMeta ? [
            'uploaded_file_meta' => [
                ...$fileMeta,
                'state' => 'pending',
            ]
        ] : []);
    }
}
