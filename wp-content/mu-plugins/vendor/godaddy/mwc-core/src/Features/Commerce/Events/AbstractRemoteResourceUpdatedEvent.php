<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Events;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Base event model for resources that may be updated remotely.
 *
 * This event should be broadcast when a resource is updated remotely.
 */
abstract class AbstractRemoteResourceUpdatedEvent
{
    use CanGetNewInstanceTrait;

    /** @var object resource object */
    protected object $resource;

    /** @var non-empty-string|null datetime string for the last time the resource was updated */
    protected ?string $lastUpdatedAt;

    /**
     * Constructor.
     *
     * @param object $resource concrete resource object that has been updated remotely
     * @param string|null $lastUpdatedAt datetime string for the last time the resource was updated
     */
    public function __construct(object $resource, ?string $lastUpdatedAt)
    {
        $this->resource = $resource;
        $this->lastUpdatedAt = TypeHelper::nonEmptyStringOrNull($lastUpdatedAt);
    }

    /**
     * Gets the resource that has been updated remotely.
     *
     * @return object
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Gets the datetime string when the resource was last updated.
     *
     * @return non-empty-string|null datetime string
     */
    public function getLastUpdatedAt() : ?string
    {
        return $this->lastUpdatedAt;
    }
}
