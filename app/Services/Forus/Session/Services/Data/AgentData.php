<?php

namespace App\Services\Forus\Session\Services\Data;

use Throwable;
use WhichBrowser\Parser;

class AgentData extends Parser
{
    /**
     * @param string $user_agent
     * @return AgentData
     */
    public static function parse(string $user_agent): AgentData
    {
        return new self($user_agent);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        try {
            $prefix = $this->camouflage ? trans('agent.unknown_imitation_browser') : '';
            $browser = $this->browser->toString();
            $os = $this->os->toString();
            $engine = $this->engine->toString();
            $device = $this->device->toString();

            if (empty($device) && empty($os) && $this->device->type == 'television') {
                $device = trans('agent.television');
            }

            if (empty($device) && $this->device->type == 'emulator') {
                $device = trans('agent.emulator');
            }

            if (!empty($browser) && !empty($os) && !empty($device)) {
                return $prefix . $browser . ' ' . trans('agent.on') . ' ' . $this->a() . $device .
                    ' ' . trans('agent.has_os') . ': ' . $os;
            }

            if (!empty($browser) && empty($os) && !empty($device)) {
                return $prefix . $browser . ' ' . trans('agent.on') . ' ' . $this->a() . $device;
            }

            if (!empty($browser) && !empty($os) && empty($device)) {
                return $prefix . $browser . ' ' . trans('agent.on') . ' ' . $os;
            }

            if (empty($browser) && !empty($os) && !empty($device)) {
                return $prefix . $this->a() . $device . ' ' . trans('agent.has_os') . ': ' . $os;
            }

            if (!empty($browser) && empty($os) && empty($device)) {
                return $prefix . $browser;
            }

            if (empty($browser) && empty($os) && !empty($device)) {
                return $prefix . $this->a() . $device;
            }

            if ($this->device->type == 'desktop' && !empty($os) && !empty($engine) && empty($device)) {
                return trans('agent.unknown_browser_based_on', ['engine' => $engine]) .
                    ' ' . trans('agent.has_os') . ': ' . $os;
            }

            if ($this->browser->stock && !empty($os) && empty($device)) {
                return $os;
            }

            if ($this->browser->stock && !empty($engine) && empty($device)) {
                return trans('agent.unknown_browser_based_on', ['engine' => $engine]);
            }

            if ($this->device->type == 'bot') {
                return trans('agent.unknown_bot');
            }

            return trans('agent.unknown_client');
        } catch (Throwable $e) {
            return parent::toString();
        }
    }

    /**
     * Return the input string prefixed with 'a' or 'an' depending on the first letter of the string.
     *
     * @return string
     */
    private function a(): string
    {
        return trans('agent.a');
    }
}
