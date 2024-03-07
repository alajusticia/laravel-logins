<?php

namespace ALajusticia\Logins\Parsers;

use ALajusticia\Logins\Contracts\UserAgentParser;
use Jenssegers\Agent\Agent as Parser;

class Agent implements UserAgentParser
{
    private Parser $parser;

    /**
     * Agent constructor.
     */
    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * Get the device name.
     */
    public function getDevice(): ?string
    {
        $device = $this->parser->device();

        return $device && $device !== 'WebKit' ? $device : null;
    }

    /**
     * Get the device type.
     */
    public function getDeviceType(): ?string
    {
        if ($this->parser->isDesktop()) {
            return 'desktop';
        } elseif ($this->parser->isMobile()) {
            return $this->parser->isTablet() ? 'tablet' : ($this->parser->isPhone() ? 'phone' : 'mobile');
        }

        return null;
    }

    /**
     * Get the platform name.
     */
    public function getPlatform(): ?string
    {
        return $this->parser->platform() ?: null;
    }

    /**
     * Get the browser name.
     */
    public function getBrowser(): ?string
    {
        return $this->parser->browser() ?: null;
    }
}
