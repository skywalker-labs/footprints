<?php

declare(strict_types=1);

namespace Skywalker\Footprints\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skywalker\Footprints\TrackableInterface;

class RegistrationTracked
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @var \Skywalker\Footprints\TrackableInterface
     */
    public $trackable;

    /**
     * Create a new event instance.
     *
     * @param \Skywalker\Footprints\TrackableInterface $trackable
     */
    public function __construct(TrackableInterface $trackable)
    {
        $this->trackable = $trackable;
    }
}
