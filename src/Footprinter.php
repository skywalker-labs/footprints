<?php

declare(strict_types=1);

namespace Skywalker\Footprints;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class Footprinter implements FootprinterInterface
{
    /**
     * Create a new Footprinter instance.
     *
     * @param string $random
     */
    public function __construct(protected string $random = '')
    {
        $this->random = $random ?: Str::random(20); // Will only be set once during requests since this class is a singleton
    }

    /** @inheritDoc */
    public function footprint(Request $request): string
    {
        $cookieName = config('footprints.cookie_name');
        $cookieName = is_string($cookieName) ? $cookieName : 'footprints';

        if ($request->hasCookie($cookieName)) {
            $val = $request->cookie($cookieName);
            return is_string($val) ? $val : (is_array($val) ? json_encode($val) ?: '' : '');
        }

        $footprint = $this->fingerprint($request);

        $duration = config('footprints.attribution_duration');
        $duration = is_numeric($duration) ? (int) $duration : 2592000;

        $domain = config('footprints.cookie_domain');
        $domain = is_string($domain) ? $domain : null;

        Cookie::queue(
            $cookieName,
            $footprint,
            $duration,
            null,
            $domain
        );

        return $footprint;
    }

    /**
     * This method will generate a fingerprint for the request based on the configuration.
     *
     * If relying on cookies then the logic of this function is not important, but if cookies are disabled this value
     * will be used to link previous requests with one another.
     *
     * @return string
     */
    protected function fingerprint(Request $request): string
    {
        // This is highly inspired from the $request->fingerprint() method
        return sha1(implode('|', array_filter([
            $request->ip(),
            $request->header('User-Agent'),
            config('footprints.uniqueness') ? $this->random : null,
        ])));
    }
}
