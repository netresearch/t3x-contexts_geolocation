<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Dto;

/**
 * Immutable value object for geolocation data.
 */
final readonly class GeoLocation
{
    public function __construct(
        public ?string $countryCode = null,
        public ?string $countryCode3 = null,
        public ?string $countryName = null,
        public ?string $continentCode = null,
        public ?string $continentName = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $region = null,
        public ?string $regionName = null,
    ) {}

    /**
     * Check if location has valid coordinates.
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null
            && $this->longitude !== null
            && !($this->latitude === 0.0 && $this->longitude === 0.0);
    }
}
