<?php

declare(strict_types=1);

namespace Skywalker\Footprints;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Skywalker\Footprints\Jobs\AssignPreviousVisits;

/**
 * Class TrackRegistrationAttribution.
 *
 * @method static void created(callable $callback)
 */
trait TrackRegistrationAttribution
{
    public static function bootTrackRegistrationAttribution(): void
    {
        // Add an observer that upon registration will automatically sync up prior visits.
        static::created(function (Model $model) {
            $model->trackRegistration(request());
        });
    }

    /**
     * Get all of the visits for the user.
     */
    public function visits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Visit::class, config('footprints.column_name'))->orderBy('created_at', 'desc');
    }

    /**
     * @deprecated Use 'trackRegistration' instead.
     */
    public function assignPreviousVisits(): void
    {
        $this->trackRegistration(request());
    }

    /**
     * Assign earlier visits using current request.
     */
    public function trackRegistration(Request $request): void
    {
        $job = new AssignPreviousVisits($request->footprint(), $this);

        if (config('footprints.async') == true) {
            dispatch($job);
        } else {
            $job->handle();
        }
    }

    /**
     * The initial attribution data that eventually led to a registration.
     */
    public function initialAttributionData(): ?Visit
    {
        return $this->hasMany(Visit::class, config('footprints.column_name'))->orderBy('created_at', 'asc')->first();
    }

    /**
     * The final attribution data before registration.
     */
    public function finalAttributionData(): ?Visit
    {
        return $this->hasMany(Visit::class, config('footprints.column_name'))->orderBy('created_at', 'desc')->first();
    }

    /**
     * Accessor for the first UTM campaign.
     */
    public function getFirstUtmCampaignAttribute(): ?string
    {
        return $this->initialAttributionData()?->utm_campaign;
    }

    /**
     * Accessor for the last UTM campaign.
     */
    public function getLastUtmCampaignAttribute(): ?string
    {
        return $this->finalAttributionData()?->utm_campaign;
    }

    /**
     * Accessor for the first UTM source.
     */
    public function getFirstUtmSourceAttribute(): ?string
    {
        return $this->initialAttributionData()?->utm_source;
    }

    /**
     * Accessor for the last UTM source.
     */
    public function getLastUtmSourceAttribute(): ?string
    {
        return $this->finalAttributionData()?->utm_source;
    }

    /**
     * Accessor for the primary device type (based on last touch).
     */
    public function getDeviceTypeAttribute(): ?string
    {
        return $this->finalAttributionData()?->device_type;
    }
}
