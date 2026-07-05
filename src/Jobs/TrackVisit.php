<?php

declare(strict_types=1);

namespace Skywalker\Footprints\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Skywalker\Footprints\Visit;

class TrackVisit implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param array<string, mixed> $attributionData
     * @param mixed $trackableId
     */
    public function __construct(
        protected array $attributionData,
        public mixed $trackableId = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $columnName = config('footprints.column_name');
        $columnName = is_string($columnName) ? $columnName : 'user_id';

        Visit::query()->create(array_merge([
            $columnName => $this->trackableId,
        ], $this->attributionData));
    }
}
