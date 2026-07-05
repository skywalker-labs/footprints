<?php

declare(strict_types=1);

namespace Skywalker\Footprints\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Skywalker\Footprints\Events\RegistrationTracked;
use Skywalker\Footprints\TrackableInterface;
use Skywalker\Footprints\Visit;

class AssignPreviousVisits implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param string $footprint
     * @param \Skywalker\Footprints\TrackableInterface $trackable
     */
    public function __construct(
        public string $footprint,
        public TrackableInterface $trackable
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var \Illuminate\Database\Eloquent\Model&\Skywalker\Footprints\TrackableInterface $trackable */
        $trackable = $this->trackable;

        $columnName = config('footprints.column_name');
        $columnName = is_string($columnName) ? $columnName : 'user_id';

        Visit::unassignedPreviousVisits($this->footprint)->update(
            [
                $columnName => $trackable->getKey(),
            ]
        );

        event(new RegistrationTracked($this->trackable));
    }
}
