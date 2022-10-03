<?php

namespace App\Models;

/**
 * App\Models\ImplementationPageConfig
 *
 * @property int $id
 * @property int $implementation_id
 * @property string $page_key
 * @property string $page_config_key
 * @property boolean $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ImplementationPageConfig extends BaseModel
{
    const CONFIG_LIST = [[
        'page_key'          => 'homepage',
        'page_config_key'   => 'show_products',
    ], [
        'page_key'          => 'homepage',
        'page_config_key'   => 'show_map',
    ], [
        'page_key'          => 'providers',
        'page_config_key'   => 'show_map',
    ], [
        'page_key'          => 'provider',
        'page_config_key'   => 'show_map',
    ]];

    /**
     * @var string[]
     */
    protected $fillable = [
        'implementation_id', 'page_key', 'page_config_key', 'is_active',
    ];

    public $timestamps = false;
}