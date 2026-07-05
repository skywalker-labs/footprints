<?php

declare(strict_types=1);

namespace Skywalker\Footprints\Tests\Unit;

use Skywalker\Footprints\Tests\TestCase;

class TrackingFilterTest extends TestCase
{
    public function test_disabled_for_post_requests()
    {
        $filter = new \Skywalker\Footprints\TrackingFilter();
        $request = \Illuminate\Http\Request::create('/test', 'POST');

        $this->assertFalse($filter->shouldTrack($request));
    }

    public function test_disabled_on_authentication()
    {
        \Illuminate\Support\Facades\Config::set('footprints.disable_on_authentication', true);
        \Illuminate\Support\Facades\Auth::shouldReceive('guard')->andReturnSelf();
        \Illuminate\Support\Facades\Auth::shouldReceive('check')->andReturn(true);

        $filter = new \Skywalker\Footprints\TrackingFilter();
        $request = \Illuminate\Http\Request::create('/test', 'GET');

        $this->assertFalse($filter->shouldTrack($request));
    }

    public function test_disabled_for_internal_links()
    {
        \Illuminate\Support\Facades\Config::set('footprints.disable_internal_links', true);

        $filter = new \Skywalker\Footprints\TrackingFilter();

        $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], ['HTTP_REFERER' => 'http://localhost/another-page']);

        $this->assertFalse($filter->shouldTrack($request));
    }

    public function test_disabled_for_landing_page()
    {
        \Illuminate\Support\Facades\Config::set('footprints.landing_page_blacklist', ['blacklisted-path']);

        $filter = new \Skywalker\Footprints\TrackingFilter();
        $request = \Illuminate\Http\Request::create('/blacklisted-path', 'GET');

        $this->assertFalse($filter->shouldTrack($request));
    }

    public function test_disabled_for_robots_tracking()
    {
        \Illuminate\Support\Facades\Config::set('footprints.disable_robots_tracking', true);

        $filter = new \Skywalker\Footprints\TrackingFilter();
        $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], ['HTTP_USER_AGENT' => 'Googlebot']);

        $this->assertFalse($filter->shouldTrack($request));
    }
}
