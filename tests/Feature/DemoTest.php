<?php

declare(strict_types=1);

namespace Skywalker\Footprints\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Skywalker\Footprints\Middleware\CaptureAttributionDataMiddleware;
use Skywalker\Footprints\Tests\TestCase;
use Skywalker\Footprints\TrackableInterface;
use Skywalker\Footprints\TrackRegistrationAttribution;
use Skywalker\Footprints\Visit;

// Dummy User Model
class DemoUser extends Model implements TrackableInterface
{
    use TrackRegistrationAttribution;

    protected $table = 'users';
    protected $guarded = [];
}

class DemoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create users table for our dummy model
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        // Use our dummy model for tracking
        config(['footprints.model' => DemoUser::class]);
        // Run synchronously for testing
        config(['footprints.async' => false]);
    }

    protected function defineRoutes($router)
    {
        $router->get('/demo-route', function () {
            return response('OK');
        })->middleware(CaptureAttributionDataMiddleware::class);
    }

    public function test_footprints_demo()
    {
        echo "\n\n🚀 Starting Footprints Demo...\n";

        // 1. Simulate an incoming request from an iPhone via a Google Ad
        echo "📡 Simulating visitor from iPhone clicking a Google Ad (utm_campaign=summer_sale)...\n";

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1',
            'Referer' => 'https://www.google.com/'
        ])->get('/demo-route?utm_campaign=summer_sale&utm_source=google');

        $response->assertStatus(200);

        // Verify visit was recorded anonymously
        $visit = Visit::first();
        echo "✅ Visit recorded anonymously!\n";
        echo "   - Browser: " . $visit->browser . "\n";
        echo "   - Device: " . $visit->device_type . "\n";
        echo "   - UTM Campaign: " . $visit->utm_campaign . "\n";
        echo "   - Referrer Domain: " . $visit->referrer_domain . "\n\n";

        // 3. Simulate User Registration
        echo "👤 Visitor decides to register...\n";

        // Inject the cookie that the Footprinter creates, so the trait can find the visit
        $cookieName = config('footprints.cookie_name');
        request()->cookies->set($cookieName, $visit->footprint);

        $user = DemoUser::create(['name' => 'Luke Skywalker']);

        echo "✅ User registered!\n";

        // 4. Test our new Trait helper methods
        echo "🔍 Accessing Attribution Data via Model Trait:\n";
        echo "   - First Campaign: " . $user->first_utm_campaign . "\n";
        echo "   - First Source: " . $user->first_utm_source . "\n";
        echo "   - Device Type: " . $user->device_type . "\n\n";

        $this->assertEquals('summer_sale', $user->first_utm_campaign);
        $this->assertEquals('Mobile', $user->device_type);
        $this->assertEquals('Safari', $visit->browser);
    }
}
