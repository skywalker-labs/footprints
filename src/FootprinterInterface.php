<?php

declare(strict_types=1);

namespace Skywalker\Footprints;

use Illuminate\Http\Request;

interface FootprinterInterface
{
    /**
     * Return the request footprint.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function footprint(Request $request): string;
}
