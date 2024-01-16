<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts\PercentageJitterProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingStrategyFactoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;

/**
 * Abstract caching service for remote entities.
 */
abstract class AbstractCachingService implements CachingServiceContract
{
    /** @var CachingStrategyFactoryContract caching strategy */
    protected CachingStrategyFactoryContract $cachingStrategyFactory;

    /** @var string plural name of the resource type (e.g. 'products' or 'customers') -- to be set by concrete implementations */
    protected string $resourceType;

    /** @var int cache TTL (in seconds) for entries */
    protected int $cacheTtl = DAY_IN_SECONDS;
    protected ?PercentageJitterProviderContract $jitterProvider = null;
    protected float $jitterRate = 0.1;

    /**
     * Constructor.
     *
     * @param CachingStrategyFactoryContract $cachingStrategyFactory
     */
    public function __construct(CachingStrategyFactoryContract $cachingStrategyFactory)
    {
        $this->cachingStrategyFactory = $cachingStrategyFactory;
    }

    /**
     * Gets the name of the cache group.
     *
     * @return string
     */
    protected function getCacheGroup() : string
    {
        return "godaddy-commerce-{$this->resourceType}";
    }

    /**
     * Get cache TTL with random jitter subtracted.
     *
     * @return int
     */
    protected function getCacheTtl() : int
    {
        if ($this->jitterProvider) {
            return $this->cacheTtl + $this->jitterProvider->setRate($this->jitterRate)->getJitter($this->cacheTtl);
        }

        return $this->cacheTtl;
    }

    /**
     * {@inheritDoc}
     */
    public function remember(string $remoteId, callable $loader) : object
    {
        $resource = $this->get($remoteId);

        if (! $resource) {
            $this->set($resource = $loader());
        }

        return $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $remoteId) : ?object
    {
        $jsonResource = $this->getCachingStrategy()->get($remoteId, $this->getCacheGroup());

        if (empty($jsonResource) || ! is_string($jsonResource)) {
            return null;
        }

        return $this->convertJsonResource($jsonResource);
    }

    /**
     * {@inheritDoc}
     */
    public function getMany(array $remoteIds) : array
    {
        return array_filter(
            array_map(
                [$this, 'convertJsonResource'],
                TypeHelper::arrayOfStrings($this->getCachingStrategy()->getMany($remoteIds, $this->getCacheGroup()))
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function set(CanConvertToArrayContract $resource) : void
    {
        $resourceRemoteId = $this->getResourceRemoteId($resource);

        $jsonEncodedResource = json_encode($resource->toArray());
        if (! is_string($jsonEncodedResource)) {
            throw new CachingStrategyException("Failed to JSON-encode resource ID {$resourceRemoteId}");
        }

        $this->getCachingStrategy()->set(
            $resourceRemoteId,
            $this->getCacheGroup(),
            $jsonEncodedResource,
            $this->getCacheTtl()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setMany(array $resources) : void
    {
        $jsonResources = [];
        foreach ($resources as $resource) {
            $jsonEncodedResource = json_encode($resource->toArray());

            if ($jsonEncodedResource) {
                $jsonResources[$this->getResourceRemoteId($resource)] = $jsonEncodedResource;
            }
        }

        $this->getCachingStrategy()->setMany(
            $this->getCacheGroup(),
            $jsonResources,
            $this->getCacheTtl()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $remoteId) : void
    {
        $this->getCachingStrategy()->remove($remoteId, $this->getCacheGroup());
    }

    /**
     * Converts a JSON-encoded resource into its DTO.
     *
     * @param string $jsonResource JSON-encoded resource
     * @return object|null
     */
    protected function convertJsonResource(string $jsonResource) : ?object
    {
        $resourceArray = json_decode($jsonResource, true);

        if (! is_array($resourceArray)) {
            return null;
        }

        return $this->makeResourceFromArray($resourceArray);
    }

    /**
     * Gets the configured caching strategy.
     *
     * @return CachingStrategyContract
     */
    protected function getCachingStrategy() : CachingStrategyContract
    {
        return $this->cachingStrategyFactory->makeCachingStrategy();
    }

    /**
     * Builds a resource DTO from an array.
     *
     * @param array<string, mixed> $resourceArray
     * @return object
     */
    abstract protected function makeResourceFromArray(array $resourceArray) : object;

    /**
     * Gets the unique remote ID for a given resource.
     *
     * @param object $resource
     * @return non-empty-string
     * @throws CommerceExceptionContract
     */
    abstract protected function getResourceRemoteId(object $resource) : string;
}
