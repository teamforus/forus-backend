<?php


namespace App\Services\Forus\Session\Services;


use App\Services\Forus\Session\Services\Data\AgentData;

class Browser
{
    /**
     * @return \Illuminate\Config\Repository|mixed
     */
    public static function isEnabled() {
        return config('forus.sessions.user_agent_enabled', true);
    }

    /**
     * @param string $user_agent
     */
    public static function getAgentData(string $user_agent): AgentData {
        return AgentData::parse($user_agent);
    }
}