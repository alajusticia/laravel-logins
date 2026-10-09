<?php

namespace ALajusticia\Logins;

use ALajusticia\Logins\Contracts\UserAgentParser;
use ALajusticia\Logins\Factories\ParserFactory;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

class RequestContext implements Arrayable
{
    protected Carbon $date;
    protected ?UserAgentParser $parser = null;
    protected ?string $userAgent;
    protected ?string $ipAddress;
    protected Position|bool|null $location = null;
    protected ?string $tokenName;

    /**
     * Device values replacing the parsed ones (see Logins::describeDeviceUsing()).
     *
     * @var array<string, ?string>
     */
    protected array $deviceDescription = [];

    /**
     * RequestContext constructor.
     *
     * @throws \Exception
     */
    public function __construct(?string $tokenName = null, bool $parseUserAgent = true, bool $ipGeolocation = true)
    {
        $this->date = Carbon::now();
        $this->userAgent = Request::userAgent();
        $this->ipAddress = Logins::ipAddress();
        $this->tokenName = $tokenName;

        if ($ipGeolocation && Logins::ipGeolocationEnabled() && ! empty($this->ipAddress)) {
            $this->location = Location::get($this->ipAddress);
        }

        if ($parseUserAgent) {
            // Initialize the parser
            $this->parser = ParserFactory::build(Config::get('logins.parser'));
        }

        $this->deviceDescription = Logins::describeDevice($this);
    }

    public function date(): Carbon
    {
        return $this->date;
    }

    /**
     * Get the parser used to parse the User-Agent header.
     */
    public function parser(): UserAgentParser
    {
        return $this->parser;
    }

    /**
     * Get the device type (desktop, mobile, tablet...).
     */
    public function deviceType(): ?string
    {
        return $this->describedOrParsed('device_type', fn () => $this->parser?->getDeviceType());
    }

    /**
     * Get the device name.
     */
    public function device(): ?string
    {
        return $this->describedOrParsed('device', fn () => $this->parser?->getDevice());
    }

    /**
     * Get the platform/OS name.
     */
    public function platform(): ?string
    {
        return $this->describedOrParsed('platform', fn () => $this->parser?->getPlatform());
    }

    /**
     * Get the browser name.
     */
    public function browser(): ?string
    {
        return $this->describedOrParsed('browser', fn () => $this->parser?->getBrowser());
    }

    /**
     * Get a value described by Logins::describeDeviceUsing(), or the parsed one.
     */
    protected function describedOrParsed(string $key, callable $parsed): ?string
    {
        return array_key_exists($key, $this->deviceDescription) ? $this->deviceDescription[$key] : $parsed();
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

    /**
     * Get the personal access token name.
     */
    public function tokenName(): ?string
    {
        return $this->tokenName;
    }

    /**
     * Transform the instance to an array.
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date()->toDateTimeString(),
            'device_type' => $this->deviceType(),
            'device' => $this->device(),
            'application' => $this->tokenName(),
            'platform' => $this->platform(),
            'browser' => $this->browser(),
            'ip' => $this->ipAddress(),
            'location' => $this->location() ? $this->location()->toArray() : null,
        ];
    }
}
