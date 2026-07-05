<?php

declare(strict_types=1);

namespace Skywalker\Footprints\Tests\Unit\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Skywalker\Footprints\Events\RegistrationTracked;
use Skywalker\Footprints\Jobs\AssignPreviousVisits;
use Skywalker\Footprints\Tests\TestCase;
use Skywalker\Footprints\TrackableInterface;

class AssignPreviousVisitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_emits_registration_tracked_event()
    {
        $trackable = \Mockery::mock(\Illuminate\Database\Eloquent\Model::class . ',' . TrackableInterface::class);
        $trackable->shouldReceive('getKey')->andReturn(123);

        Event::fake();

        $job = new AssignPreviousVisits('test-footprint', $trackable);
        $job->handle(); // We are not checking the "queue" part of the job, only that it does actually dispatch the event

        Event::assertDispatched(RegistrationTracked::class, function ($event) use ($trackable) {
            return $event->trackable === $trackable;
        });
    }
}
