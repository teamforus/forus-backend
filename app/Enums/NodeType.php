<?php

namespace App\Enums;

enum NodeType: string
{
    case Decision = 'decision'; // strictly yes / no
    case Category = 'category'; // N options
    case Result = 'result';     // leaf, has `eligible`
}
