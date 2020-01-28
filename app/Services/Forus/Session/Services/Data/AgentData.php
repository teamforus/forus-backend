<?php


namespace App\Services\Forus\Session\Services\Data;

use WhichBrowser\Parser;

/**
 * Class LocationData
 * @package App\Services\Forus\Session\Services\Data
 */
class AgentData extends Parser
{
    /**
     * @param string $user_agent
     * @return AgentData
     */
    public static function parse(string $user_agent) {
        return new self($user_agent);
    }

    /**
     * Return the input string prefixed with 'a' or 'an' depending on the first letter of the string
     *
     * @param  string $s The string that will be prefixed
     * @return string
     */
    private function a($s)
    {
        return (preg_match("/^[aeiou]/i", $s) ? 'an ' : 'a ') . $s;
    }

    /**
     * @return string
     */
    public function toString()
    {
        try {
            $prefix = $this->camouflage ? 'an unknown browser that imitates ' : '';
            $browser = $this->browser->toString();
            $os = $this->os->toString();
            $engine = $this->engine->toString();
            $device = $this->device->toString();

            if (empty($device) && empty($os) && $this->device->type == 'television') {
                $device = 'television';
            }

            if (empty($device) && $this->device->type == 'emulator') {
                $device = 'emulator';
            }

            if (!empty($browser) && !empty($os) && !empty($device)) {
                return $prefix . $browser . ' on ' . $this->a($device) . ' running ' . $os;
            }

            if (!empty($browser) && empty($os) && !empty($device)) {
                return $prefix . $browser . ' on ' . $this->a($device);
            }

            if (!empty($browser) && !empty($os) && empty($device)) {
                return $prefix . $browser . ' on ' . $os;
            }

            if (empty($browser) && !empty($os) && !empty($device)) {
                return $prefix . $this->a($device) . ' running ' . $os;
            }

            if (!empty($browser) && empty($os) && empty($device)) {
                return $prefix . $browser;
            }

            if (empty($browser) && empty($os) && !empty($device)) {
                return $prefix . $this->a($device);
            }

            if ($this->device->type == 'desktop' && !empty($os) && !empty($engine) && empty($device)) {
                return 'an unknown browser based on ' . $engine . ' running on ' . $os;
            }

            if ($this->browser->stock && !empty($os) && empty($device)) {
                return $os;
            }

            if ($this->browser->stock && !empty($engine) && empty($device)) {
                return 'an unknown browser based on ' . $engine;
            }

            if ($this->device->type == 'bot') {
                return 'an unknown bot';
            }

            return 'an unknown client';
        } catch (\Exception $exception) {
            return parent::toString();
        }
    }
}