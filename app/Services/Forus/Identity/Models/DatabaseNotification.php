<?php

namespace App\Services\Forus\Identity\Models;

use Illuminate\Notifications\DatabaseNotification as DatabaseNotificationModel;

/**
 * App\Services\Forus\Identity\Models\DatabaseNotification
 *
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property array $data
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $notifiable
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\DatabaseNotification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\DatabaseNotification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\DatabaseNotification query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\DatabaseNotification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\DatabaseNotification whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\DatabaseNotification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\DatabaseNotification whereNotifiableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\DatabaseNotification whereNotifiableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\DatabaseNotification whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\DatabaseNotification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\DatabaseNotification whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DatabaseNotification extends DatabaseNotificationModel
{}
