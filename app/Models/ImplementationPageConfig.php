<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ImplementationPageConfig
 *
 * @property int $id
 * @property int $implementation_id
 * @property string|null $page_key
 * @property string|null $page_config_key
 * @property boolean $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ImplementationPageConfig extends Model
{
    const CONFIG_LIST = [[
        'config_key'   => 'show_products',
        'page_key'     => 'homepage',
    ], [
        'config_key'   => 'show_products',
        'page_key'     => 'homepage',
    ], [
        'config_key'   => 'show_map',
        'page_key'     => 'providers',
    ], [
        'config_key'   => 'show_map',
        'page_key'     => 'provider',
    ]];
}
