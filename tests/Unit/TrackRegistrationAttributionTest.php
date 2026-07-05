<?php

declare(strict_types=1);

namespace Skywalker\Footprints\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Skywalker\Footprints\Jobs\AssignPreviousVisits;
use Skywalker\Footprints\Tests\TestCase;
use Skywalker\Footprints\TrackableInterface;
use Skywalker\Footprints\TrackRegistrationAttribution;

class TrackRegistrationAttributionTest extends TestCase
{
    #[Test]
    public function test_dispatches_assign_previous_visits_job_when_configured_as_async()
    {
        Config::set('footprints.async', true);

        Bus::fake();

        $request = $this->mock(Request::class, function (MockInterface $mock) {
            $mock->shouldReceive('footprint')->andReturn('ABC123');
        });

        $trackable = new User();

        $trackable->trackRegistration($request);

        Bus::assertDispatched(AssignPreviousVisits::class, function ($job) use ($trackable) {
            return $job->footprint == 'ABC123' && $job->trackable == $trackable;
        });
    }

    #[Test]
    public function test_does_not_dispatch_assign_previous_visits_job_when_configured_as_sync()
    {
        Config::set('footprints.async', false);

        Bus::fake();

        (new User())->trackRegistration(new Request());

        Bus::assertNotDispatched(AssignPreviousVisits::class);
    }

    #[Test]
    public function test_helper_attributes_return_correct_attribution_data()
    {
        $user = $this->mock(User::class)->makePartial();
        
        $visit1 = new \Skywalker\Footprints\Visit([
            'utm_campaign' => 'first_campaign',
            'utm_source' => 'first_source',
            'device_type' => 'Desktop',
            'browser' => 'Chrome',
        ]);

        $visit2 = new \Skywalker\Footprints\Visit([
            'utm_campaign' => 'last_campaign',
            'utm_source' => 'last_source',
            'device_type' => 'Mobile',
            'browser' => 'Safari',
        ]);

        $user->shouldReceive('initialAttributionData')->andReturn($visit1);
        $user->shouldReceive('finalAttributionData')->andReturn($visit2);

        $this->assertEquals('first_campaign', $user->first_utm_campaign);
        $this->assertEquals('last_campaign', $user->last_utm_campaign);
        $this->assertEquals('first_source', $user->first_utm_source);
        $this->assertEquals('last_source', $user->last_utm_source);
        $this->assertEquals('Mobile', $user->device_type);
    }
}

class User extends \Illuminate\Database\Eloquent\Model implements TrackableInterface
{
    use TrackRegistrationAttribution;

    public $id = 123;
}
