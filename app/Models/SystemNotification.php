<?php

namespace App\Models;

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
     * @param string $key
     * @param string $type
     * @param string|null $implementation_key
     * @param int|null $fund_id
     * @return NotificationTemplate|null
     */
    public static function findTemplate(
        string $key,
        string $type,
        ?string $implementation_key = null,
        ?int $fund_id = null
    ): ?NotificationTemplate {
        $systemNotification = SystemNotification::where(compact('key'))->first();
        $generalImplementation = Implementation::general();
        $currentImplementation = Implementation::byKey($implementation_key);

        /** @var NotificationTemplate $generalTemplate */
        $generalTemplate = $systemNotification->templates()->where([
            'implementation_id' => $generalImplementation->id,
            'formal' => !($currentImplementation ?: $generalImplementation)->informal_communication,
            'type' => $type,
        ])->first();

        /** @var NotificationTemplate|null $template */
        $template = $currentImplementation ? $systemNotification->templates()->where([
            'implementation_id' => $currentImplementation->id,
            'formal' => !$currentImplementation->informal_communication,
            'type' => $type,
        ])->where(function(Builder $builder) use ($fund_id, $currentImplementation, $key, $type) {
            $builder->whereNull('fund_id');

            if ($currentImplementation->allow_per_fund_notification_templates) {
                $builder->orWhere('fund_id', $fund_id);
            }
        })->orderByDesc('fund_id')->first() : null;

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
    public static function getByKey(string $key): ?self
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
}