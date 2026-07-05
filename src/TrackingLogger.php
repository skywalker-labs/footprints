<?php

declare(strict_types=1);

namespace Skywalker\Footprints;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use Skywalker\Footprints\Jobs\TrackVisit;

class TrackingLogger implements TrackingLoggerInterface
{
    /**
     * Create a new TrackingLogger instance.
     */
    public function __construct(
        protected Request $request,
        protected ?Agent $agent = null
    ) {
        $this->agent = $agent ?? new Agent();
        $this->agent->setUserAgent((string) $this->request->userAgent());
    }

    /**
     * Track the request.
     */
    public function track(Request $request): Request
    {
        $job = new TrackVisit($this->captureAttributionData(), Auth::user()?->getAuthIdentifier());

        if (config('footprints.async') == true) {
            dispatch($job);
        } else {
            $job->handle();
        }

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    protected function captureAttributionData(): array
    {
        $attributes = array_merge(
            [
                'footprint'         => $this->request->footprint(), // @phpstan-ignore-line
                'ip'                => $this->captureIp(),
                'landing_domain'    => $this->captureLandingDomain(),
                'landing_page'      => $this->captureLandingPage(),
                'landing_params'    => $this->captureLandingParams(),
                'referral'          => $this->captureReferral(),
                'gclid'             => $this->captureGCLID(),
                'device_type'       => $this->captureDeviceType(),
                'browser'           => $this->captureBrowser(),
            ],
            $this->captureUTM(),
            $this->captureReferrer(),
            $this->getCustomParameter()
        );

        return array_map(function ($item) {
            return is_string($item) ? substr($item, 0, 255) : $item;
        }, $attributes);
    }

    protected function captureDeviceType(): ?string
    {
        if ($this->agent?->isMobile()) {
            return 'Mobile';
        } elseif ($this->agent?->isTablet()) {
            return 'Tablet';
        } elseif ($this->agent?->isDesktop()) {
            return 'Desktop';
        }

        return null;
    }

    protected function captureBrowser(): ?string
    {
        $browser = $this->agent?->browser();
        return is_string($browser) && $browser !== '' ? $browser : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getCustomParameter(): array
    {
        $arr = [];
        $parameters = config('footprints.custom_parameters');

        if ($parameters && is_array($parameters)) {
            foreach ($parameters as $parameter) {
                if (is_string($parameter)) {
                    $arr[$parameter] = $this->request->input($parameter);
                }
            }
        }

        return $arr;
    }

    /**
     * @return string|null
     */
    protected function captureIp(): ?string
    {
        if (! config('footprints.attribution_ip')) {
            return null;
        }

        return $this->request->ip();
    }

    /**
     * @return string
     */
    protected function captureLandingDomain(): string
    {
        return $this->request->getHost();
    }

    /**
     * @return string
     */
    protected function captureLandingPage(): string
    {
        return $this->request->path();
    }

    /**
     * @return string|null
     */
    protected function captureLandingParams(): ?string
    {
        return $this->request->getQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    protected function captureUTM(): array
    {
        $parameters = ['utm_source', 'utm_campaign', 'utm_medium', 'utm_term', 'utm_content'];

        $utm = [];

        foreach ($parameters as $parameter) {
            $utm[$parameter] = $this->request->input($parameter);
        }

        return $utm;
    }

    /**
     * @return array<string, mixed>
     */
    protected function captureReferrer(): array
    {
        $referrer = [];

        $referrer['referrer_url'] = $this->request->headers->get('referer');

        if ($referrer['referrer_url']) {
            $parsedUrl = parse_url((string) $referrer['referrer_url']);
            $referrer['referrer_domain'] = $parsedUrl['host'] ?? null;
        } else {
            $referrer['referrer_domain'] = null;
        }

        return $referrer;
    }

    /**
     * @return string|null
     */
    protected function captureGCLID(): ?string
    {
        $gclid = $this->request->input('gclid');
        return is_string($gclid) ? $gclid : null;
    }

    /**
     * @return string|null
     */
    protected function captureReferral(): ?string
    {
        $ref = $this->request->input('ref');
        return is_string($ref) ? $ref : null;
    }
}
