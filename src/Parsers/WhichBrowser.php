<?php

namespace ALajusticia\Logins\Parsers;

use ALajusticia\Logins\Contracts\UserAgentParser;
use Illuminate\Support\Facades\Request;
use WhichBrowser\Parser;

class WhichBrowser implements UserAgentParser
{
    private Parser $parser;

    /**
     * WhichBrowser constructor.
     */
    public function __construct()
    {
        $this->parser = new Parser(Request::userAgent());
    }

    /**
     * Get the device name.
     */
    public function getDevice(): ?string
    {
        return trim($this->parser->device->toString()) ?: $this->getDeviceByManufacturerAndModel();
    }

    /**
     * Get the device name by manufacturer and model.
     */
    protected function getDeviceByManufacturerAndModel(): ?string
    {
        return trim($this->parser->device->getManufacturer().' '.$this->parser->device->getModel()) ?: null;
    }

    /**
     * Get the device type.
     */
    public function getDeviceType(): ?string
    {
        return trim($this->parser->device->type) ?: null;
    }

    /**
     * Get the platform name.
     */
    public function getPlatform(): ?string
    {
        return trim($this->parser->os->toString()) ?: null;
    }

    /**
     * Get the browser name.
     */
    public function getBrowser(): ?string
    {
        return $this->parser->browser->name;
    }
}
