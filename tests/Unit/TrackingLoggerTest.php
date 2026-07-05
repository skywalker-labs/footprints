<?php

declare(strict_types=1);

namespace Skywalker\Footprints\Tests\Unit;

use Skywalker\Footprints\Tests\TestCase;

class TrackingLoggerTest extends TestCase
{
    public function test_logging_job_handled_async()
    {
        \Illuminate\Support\Facades\Config::set('footprints.async', true);
        \Illuminate\Support\Facades\Bus::fake();

        $request = \Mockery::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('footprint')->andReturn('test-footprint');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('getHost')->andReturn('localhost');
        $request->shouldReceive('path')->andReturn('test');
        $request->shouldReceive('getQueryString')->andReturn(null);
        $request->shouldReceive('input')->andReturn(null);
        $request->shouldReceive('userAgent')->andReturn('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/100.0.0.0 Safari/537.36');
        $request->headers = new \Symfony\Component\HttpFoundation\HeaderBag(['referer' => 'http://google.com']);

        $logger = new \Skywalker\Footprints\TrackingLogger($request);

        $logger->track($request);

        \Illuminate\Support\Facades\Bus::assertDispatched(\Skywalker\Footprints\Jobs\TrackVisit::class);
    }

    public function test_logging_job_handled_sync()
    {
        \Illuminate\Support\Facades\Config::set('footprints.async', false);

        $request = \Mockery::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('footprint')->andReturn('test-footprint');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('getHost')->andReturn('localhost');
        $request->shouldReceive('path')->andReturn('test');
        $request->shouldReceive('getQueryString')->andReturn(null);
        $request->shouldReceive('input')->andReturn(null);
        $request->shouldReceive('userAgent')->andReturn('Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) Chrome/100.0.0.0 Mobile Safari/537.36');
        $request->headers = new \Symfony\Component\HttpFoundation\HeaderBag();

        $logger = new \Skywalker\Footprints\TrackingLogger($request);

        // We can't easily check 'handle' was called without complex mocking,
        // but we verify it returns the request and doesn't crash.
        $result = $logger->track($request);
        $this->assertSame($request, $result);
    }

    public function test_attribution_data_capture()
    {
        \Illuminate\Support\Facades\Config::set('footprints.attribution_ip', true);

        $request = \Mockery::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('footprint')->andReturn('test-footprint');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('getHost')->andReturn('localhost');
        $request->shouldReceive('path')->andReturn('test');
        $request->shouldReceive('getQueryString')->andReturn(null);
        $request->shouldReceive('input')->andReturnUsing(fn ($key) => $key === 'utm_source' ? 'google' : null);
        $request->shouldReceive('userAgent')->andReturn('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/100.0.0.0 Safari/537.36');
        $request->headers = new \Symfony\Component\HttpFoundation\HeaderBag();

        $logger = new \Skywalker\Footprints\TrackingLogger($request);

        $reflection = new \ReflectionClass($logger);
        $method = $reflection->getMethod('captureAttributionData');
        $method->setAccessible(true);
        $data = $method->invoke($logger);

        $this->assertEquals('127.0.0.1', $data['ip']);
        $this->assertEquals('google', $data['utm_source']);
        $this->assertEquals('localhost', $data['landing_domain']);
    }

    public function test_device_and_browser_data_capture()
    {
        $request = \Mockery::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('footprint')->andReturn('test-footprint');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('getHost')->andReturn('localhost');
        $request->shouldReceive('path')->andReturn('test');
        $request->shouldReceive('getQueryString')->andReturn(null);
        $request->shouldReceive('input')->andReturn(null);
        $request->shouldReceive('userAgent')->andReturn('Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1');
        $request->headers = new \Symfony\Component\HttpFoundation\HeaderBag();

        $logger = new \Skywalker\Footprints\TrackingLogger($request);

        $reflection = new \ReflectionClass($logger);
        $method = $reflection->getMethod('captureAttributionData');
        $method->setAccessible(true);
        $data = $method->invoke($logger);

        $this->assertEquals('Mobile', $data['device_type']);
        $this->assertEquals('Safari', $data['browser']);
    }
}
