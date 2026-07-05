<?php

declare(strict_types=1);

namespace Skywalker\Footprints\Middleware;

use Closure;
use Illuminate\Http\Request;
use Skywalker\Footprints\TrackingFilterInterface;
use Skywalker\Footprints\TrackingLoggerInterface;

class CaptureAttributionDataMiddleware
{
    /**
     * @var \Skywalker\Footprints\TrackingFilterInterface
     */
    protected $filter;

    /**
     * @var \Skywalker\Footprints\TrackingLoggerInterface
     */
    protected $logger;

    /**
     * Create a new CaptureAttributionDataMiddleware instance.
     *
     * @param \Skywalker\Footprints\TrackingFilterInterface $filter
     * @param \Skywalker\Footprints\TrackingLoggerInterface $logger
     */
    public function __construct(TrackingFilterInterface $filter, TrackingLoggerInterface $logger)
    {
        $this->filter = $filter;
        $this->logger = $logger;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->filter->shouldTrack($request)) {
            $request = $this->logger->track($request);
        }

        return $next($request);
    }
}
