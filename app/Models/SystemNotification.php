<?php

namespace App\Models;

use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;

/**
 * App\Models\SystemNotification.
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
 * @method static Builder<static>|SystemNotification newModelQuery()
 * @method static Builder<static>|SystemNotification newQuery()
 * @method static Builder<static>|SystemNotification query()
 * @method static Builder<static>|SystemNotification whereCreatedAt($value)
 * @method static Builder<static>|SystemNotification whereDatabase($value)
 * @method static Builder<static>|SystemNotification whereEditable($value)
 * @method static Builder<static>|SystemNotification whereGroup($value)
 * @method static Builder<static>|SystemNotification whereId($value)
 * @method static Builder<static>|SystemNotification whereKey($value)
 * @method static Builder<static>|SystemNotification whereMail($value)
 * @method static Builder<static>|SystemNotification whereOptional($value)
 * @method static Builder<static>|SystemNotification whereOrder($value)
 * @method static Builder<static>|SystemNotification wherePush($value)
 * @method static Builder<static>|SystemNotification whereUpdatedAt($value)
 * @method static Builder<static>|SystemNotification whereVisible($value)
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
                ->where('formal', !$implementation->informal_communication);

            if ($this->usesFundTemplate($implementation, $fund_id)) {
                $databaseTemplates = $databaseTemplates->whereIn('fund_id', [null, $fund_id]);
            } else {
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
     * @param Implementation|null $implementation
     * @param int|null $fundId
     * @return array
     */
    public function channels(?Implementation $implementation = null, ?int $fundId = null): array
    {
        $config = $this->getConfig($implementation);
        $fundConfig = $this->usesFundConfig($implementation, $fundId) ? $this->getConfig($implementation, $fundId) : null;

        $implementationEnabled = $config?->enable_all ?? true;
        $fundEnabled = $fundConfig?->enable_all ?? true;
        $enableAll = $implementationEnabled && $fundEnabled;

        return !$enableAll ? [] : array_filter([
            ($this->database && ($config?->enable_database ?? true) && ($fundConfig?->enable_database ?? true)) ? 'database' : false,
            ($this->mail && ($config?->enable_mail ?? true) && ($fundConfig?->enable_mail ?? true)) ? 'mail' : false,
            ($this->push && ($config?->enable_push ?? true) && ($fundConfig?->enable_push ?? true)) ? 'push' : false,
        ]);
    }

    /**
     * @param Implementation|null $implementation
     * @param int|null $fundId
     * @return SystemNotificationConfig|null
     */
    public function getConfig(?Implementation $implementation, ?int $fundId = null): ?SystemNotificationConfig
    {
        if (!$this->optional || !$implementation) {
            return null;
        }

        $configs = $this->system_notification_configs->where('implementation_id', $implementation->id);

        return is_null($fundId)
            ? $configs->whereNull('fund_id')->first()
            : $configs->where('fund_id', $fundId)->first();
    }

    /**
     * @param Implementation $implementation
     * @param array $values
     * @param int|null $fundId
     * @return SystemNotificationConfig
     */
    public function updateConfig(Implementation $implementation, array $values, ?int $fundId = null): SystemNotificationConfig
    {
        $query = $this->system_notification_configs()->where('implementation_id', $implementation->id);
        $configFundId = $this->usesFundConfig($implementation, $fundId) ? $fundId : null;

        if (is_null($configFundId)) {
            $configs = $query->whereNull('fund_id')->orderBy('id')->get();

            if ($configs->count() > 1) {
                $configs->slice(1)->each->delete();
            }
        }

        return $this->system_notification_configs()->updateOrCreate([
            'implementation_id' => $implementation->id,
            'fund_id' => $configFundId,
        ], $values);
    }

    /**
     * @param Implementation $implementation
     * @param array $templates
     * @param int|null $fundId
     * @return void
     */
    public function syncTemplates(Implementation $implementation, array $templates, ?int $fundId = null): void
    {
        foreach ($templates as $template) {
            $templateFundId = $this->resolveTemplateFundId($implementation, $template, $fundId);

            $this->templates()->updateOrCreate([
                'implementation_id' => $implementation->id,
                'fund_id' => $templateFundId,
                'type' => $template['type'],
                'formal' => $template['formal'],
            ], Arr::only($template, [
                'title', 'content',
            ]));
        }
    }

    /**
     * @param Implementation $implementation
     * @param array $templates
     * @param int|null $fundId
     * @return void
     */
    public function removeTemplates(Implementation $implementation, array $templates, ?int $fundId = null): void
    {
        foreach ($templates as $template) {
            $templateFundId = $this->resolveTemplateFundId($implementation, $template, $fundId);

            $this->templates()->where([
                'implementation_id' => $implementation->id,
                'fund_id' => $templateFundId,
                'type' => $template['type'],
                'formal' => $template['formal'],
            ])->delete();
        }
    }

    /**
     * @param Implementation $implementation
     * @param array $template
     * @param int|null $fundId
     * @return int|null
     */
    public function resolveTemplateFundId(Implementation $implementation, array $template, ?int $fundId): ?int
    {
        $templateFundId = Arr::get($template, 'fund_id');

        if ($this->usesFundTemplate($implementation, $fundId)) {
            return $fundId;
        }

        return $this->usesFundTemplate($implementation, $templateFundId) ? $templateFundId : null;
    }

    /**
     * @param Implementation|null $implementation
     * @param int|null $fundId
     * @return bool
     */
    public function usesFundConfig(?Implementation $implementation, ?int $fundId): bool
    {
        return $this->optional && $this->usesFundTemplate($implementation, $fundId);
    }

    /**
     * @param Implementation|null $implementation
     * @param int|null $fundId
     * @return bool
     */
    public function usesFundTemplate(?Implementation $implementation, ?int $fundId): bool
    {
        return $implementation?->allow_per_fund_notification_templates && !is_null($fundId);
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
