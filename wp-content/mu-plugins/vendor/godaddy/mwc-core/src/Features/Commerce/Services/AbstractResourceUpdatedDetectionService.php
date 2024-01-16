<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\AbstractRemoteResourceUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\AbstractResourceUpdatesRepository;

/**
 * Abstract resource updated event broadcast service.
 *
 * This service is responsible to broadcast a {@see AbstractRemoteResourceUpdatedEvent} when it detects that a resource has been updated remotely.
 * Child implementations must define the concrete event class and resource updates repository instance to use.
 * Methods to get the remote resource ID and the current updated at date can be overridden accordingly.
 */
abstract class AbstractResourceUpdatedDetectionService
{
    /** @var class-string<AbstractRemoteResourceUpdatedEvent> child classes can define the concrete event class to use for the event to broadcast */
    protected string $resourceUpdatedEventClass;

    /** @var AbstractResourceUpdatesRepository */
    protected AbstractResourceUpdatesRepository $resourceUpdatesRepository;

    /**
     * Determines whether a resource updated event should be broadcast.
     *
     * @param string $resourceUpdatedAt
     * @param string|null $lastUpdatedAt
     * @return bool
     * @phpstan-assert-if-true !null $lastUpdatedAt
     */
    protected function shouldBroadcastResourceUpdatedEvent(string $resourceUpdatedAt, ?string $lastUpdatedAt) : bool
    {
        //@TODO This method will be implemented in MWC-14127 {ajaynes 09-22-2023}
        return true;
    }

    /**
     * Maybe broadcasts a {@see AbstractRemoteResourceUpdatedEvent} if the resource last updated at date is newer than the last known locally.
     *
     * @param object|object[] $resources one or more resources of the same type
     * @return void
     */
    public function maybeBroadcastResourceUpdatedEvent($resources) : void
    {
        //@TODO This method will be implemented in MWC-13152 {ajaynes 09-22-2023}
    }

    /**
     * Gets the resource ID.
     *
     * @param object $resource
     * @return string|null
     */
    protected function getRemoteResourceId(object $resource) : ?string
    {
        //@TODO This method will be implemented in MWC-13153 {ajaynes 09-22-2023}
        return null;
    }

    /**
     * Gets the remote resource's updatedAt value.
     *
     * @param object $resource
     * @return string|null
     */
    protected function getRemoteUpdatedAt(object $resource) : ?string
    {
        //@TODO This method will be implemented in MWC-13154 {ajaynes 09-22-2023}
        return null;
    }

    /**
     * Gets the last known updatedAt value for a resource.
     *
     * @param string $remoteId
     * @return non-empty-string|null
     */
    protected function getLocalUpdatedAt(string $remoteId) : ?string
    {
        return $this->resourceUpdatesRepository->getUpdatedAt($remoteId);
    }

    /**
     * Gets an instance of the resource updated event.
     *
     * @param object $resource
     * @param ?string $lastUpdatedAt
     * @return ?AbstractRemoteResourceUpdatedEvent
     */
    protected function getResourceUpdatedEventInstance(object $resource, ?string $lastUpdatedAt) : ?AbstractRemoteResourceUpdatedEvent
    {
        //@TODO This method will be implemented in MWC-13156 {ajaynes 09-22-2023}
        return null;
    }
}
