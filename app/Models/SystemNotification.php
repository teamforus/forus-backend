<?php

namespace App\Models;

use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\SystemNotification
 *
 * @property int $id
 * @property string $key
 * @property bool $mail
 * @property bool $push
 * @property bool $database
 * @property bool $optional
 * @property bool $visible
 * @property bool $editable
 * @property int $order
 * @property string|null $group
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SystemNotificationConfig[] $system_notification_configs
 * @property-read int|null $system_notification_configs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\NotificationTemplate[] $templates
 * @property-read int|null $templates_count
 * @property-read \App\Models\NotificationTemplate|null $templates_database
 * @property-read \App\Models\NotificationTemplate|null $templates_mail
 * @property-read \App\Models\NotificationTemplate|null $templates_push
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification query()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification whereDatabase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification whereEditable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification whereMail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification whereOptional($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification wherePush($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotification whereVisible($value)
 * @mixin \Eloquent
 */
class SystemNotification extends Model
{
    protected $fillable = [
        'key', 'mail', 'push', 'database', 'optional', 'visible', 'editable', 'group', 'order',
    ];

    protected $casts = [
        'mail' => 'boolean',
        'push' => 'boolean',
        'database' => 'boolean',
        'optional' => 'boolean',
        'visible' => 'boolean',
        'editable' => 'boolean',
    ];

    protected $perPage = 100;

    /**
     * @param Implementation|null $implementation
     * @param int|null $fund_id
     * @param string $type
     * @return NotificationTemplate|null
     */
    public function findTemplate(
        ?Implementation $implementation,
        ?int $fund_id,
        string $type,
    ): ?NotificationTemplate {
        $template = null;
        $generalImplementation = Implementation::general();
        $databaseTemplates = $this->templates->where('type', $type);

        $generalTemplate = $databaseTemplates
            ->where('implementation_id', $generalImplementation->id)
            ->where('formal', !($implementation ?: $generalImplementation)->informal_communication)
            ->first();

        if ($implementation) {
            $databaseTemplates = $databaseTemplates
                ->where('implementation_id', $implementation->id)
                ->where('formal', !$implementation->informal_communication)
                ->whereIn('fund_id', [null, $fund_id]);

            if (!$implementation->allow_per_fund_notification_templates) {
                $databaseTemplates = $databaseTemplates->where('fund_id', null);
            }

            $template = $databaseTemplates->sortByDesc('fund_id')->first();
        }

        return $template ?: $generalTemplate;
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function system_notification_configs(): HasMany
    {
        return $this->hasMany(SystemNotificationConfig::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function templates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function templates_mail(): HasOne
    {
        return $this->hasOne(NotificationTemplate::class)->where([
            'type' => 'mail',
        ]);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function templates_push(): HasOne
    {
        return $this->hasOne(NotificationTemplate::class)->where([
            'type' => 'push',
        ]);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function templates_database(): HasOne
    {
        return $this->hasOne(NotificationTemplate::class)->where([
            'type' => 'database',
        ]);
    }

    /**
     * @param string $key
     * @return SystemNotification|null
     */
    public static function findByKey(string $key): ?self
    {
        return self::where('key', $key)->first();
    }

    /**
     * @return array
     */
    public function baseChannels(): array
    {
        return array_filter([
            $this->database ? 'database' : false,
            $this->mail ? 'mail' : false,
            $this->push ? 'push' : false,
        ]);
    }

    /**
     * @param int|null $implementationId
     * @return array
     */
    public function channels(int $implementationId = null): array
    {
        /** @var SystemNotificationConfig|null $configs */
        $configs = $this->optional ? $this->system_notification_configs : null;
        $config = $configs?->where('implementation_id', $implementationId)->first();

        return ($config && !$config->enable_all) ? [] : array_filter([
            ($this->database && (!$config || $config->enable_database)) ? 'database' : false,
            ($this->mail && (!$config || $config->enable_mail)) ? 'mail' : false,
            ($this->push && (!$config || $config->enable_push)) ? 'push' : false,
        ]);
    }

    /**
     * @param array $fundIds
     * @return Carbon|null
     */
    public function getLastSentDate(array $fundIds): ?Carbon
    {
        return EmailLog::query()
            ->where('system_notification_key', $this->key)
            ->whereHas('event_log', fn (Builder $builder) => $builder->whereIn('data->fund_id', $fundIds))
            ->latest('created_at')
            ->first()
            ?->created_at;
    }
}