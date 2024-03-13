<?php

namespace ALajusticia\Logins;

use ALajusticia\Logins\Contracts\UserAgentParser;
use ALajusticia\Logins\Factories\ParserFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

class RequestContext
{
    protected Carbon $date;
    protected ?UserAgentParser $parser = null;
    protected ?string $userAgent;
    protected ?string $ipAddress;
    protected Position|bool|null $location = null;

    /**
     * RequestContext constructor.
     *
     * @throws \Exception
     */
    public function __construct(bool $parseUserAgent = true, bool $ipGeolocation = true)
    {
        $this->date = Carbon::now();
        $this->userAgent = Request::userAgent();
        $this->ipAddress = Logins::ipAddress();

        if ($ipGeolocation && Logins::ipGeolocationEnabled() && ! empty($this->ipAddress)) {
            $this->location = Location::get($this->ipAddress);
        }

        if ($parseUserAgent) {
            // Initialize the parser
            $this->parser = ParserFactory::build(Config::get('logins.parser'));
        }
    }

    /**
     * Get the parser used to parse the User-Agent header.
     */
    public function parser(): UserAgentParser
    {
        return $this->parser;
    }

    /**
     * Get the full unparsed User-Agent header.
     */
    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Get the client's IP address.
     */
    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * Get the client's location.
     */
    public function location(): Position|bool|null
    {
        return $this->location;
    }
}
