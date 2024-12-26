<?php

namespace App\Services\Forus\Session\Services;

use App\Services\Forus\Session\Services\Data\AgentData;
use Illuminate\Support\Facades\Config;

class Browser
{
    /**
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return (bool) Config::get('forus.sessions.user_agent_enabled', true);
    }

    /**
     * @param string $user_agent
     * @return AgentData|null
     */
    public static function getAgentData(string $user_agent): ?AgentData
    {
        return AgentData::parse($user_agent);
    }
}