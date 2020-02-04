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
     * @return AgentData|null
     */
    public static function getAgentData(string $user_agent) {
        return AgentData::parse($user_agent);
    }
}