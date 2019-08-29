<?php

namespace App\Models;

class EmailPreference extends Model
{
    protected $fillable = ['identity_address', 'email', 'subscribed'];
}
