<?php

declare(strict_types=1);

namespace Skywalker\Footprints;

use Illuminate\Http\Request;

/**
 * Interface TrackableInterface
 * @property int|string $id
 */
interface TrackableInterface
{
    /**
     * Assign earlier visits using current request.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function trackRegistration(Request $request): void;
}
