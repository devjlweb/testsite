<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Traits;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LevelsCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\SummariesCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;

trait CanClearInventoryCacheTrait
{
    protected LevelsCachingService $levelsCachingService;
    protected SummariesCachingService $summariesCachingService;

    /**
     * Clears the inventory cache for the given product IDs.
     *
     * @param string[] $productIds
     */
    protected function clearCache(array $productIds) : void
    {
        try {
            array_map(
                function ($productId) {
                    $this->levelsCachingService->remove($productId);
                    $this->summariesCachingService->remove($productId);
                },
                $productIds
            );
        } catch (CachingStrategyException $exception) {
            SentryException::getNewInstance('Could not clear inventory cache.', $exception);
        }
    }
}
