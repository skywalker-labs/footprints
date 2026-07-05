<?php

declare(strict_types=1);

namespace Skywalker\Footprints\Tests\Unit\Macros;

use Skywalker\Footprints\Tests\TestCase;

class RequestFootprintMacroTest extends TestCase
{
    public function test_footprint_macro()
    {
        $request = \Illuminate\Http\Request::create('/test', 'GET');

        // The macro is registered in the ServiceProvider
        $this->assertTrue(\Illuminate\Http\Request::hasMacro('footprint'));

        $this->assertNotEmpty($request->footprint());
    }
}
