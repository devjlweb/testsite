<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LevelsCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\SummariesCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Traits\CanClearInventoryCacheTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use GoDaddy\WordPress\MWC\Core\Interceptors\Handlers\AbstractInterceptorHandler;

class AddToCartCacheHandler extends AbstractInterceptorHandler
{
    use CanClearInventoryCacheTrait;

    protected ProductMapRepository $productMapRepository;
    protected LevelsCachingService $levelsCachingService;
    protected SummariesCachingService $summariesCachingService;

    /**
     * @param ProductMapRepository $productMapRepository
     * @param LevelsCachingService $levelsCachingService
     * @param SummariesCachingService $summariesCachingService
     */
    public function __construct(
        ProductMapRepository $productMapRepository,
        LevelsCachingService $levelsCachingService,
        SummariesCachingService $summariesCachingService
    ) {
        $this->productMapRepository = $productMapRepository;
        $this->levelsCachingService = $levelsCachingService;
        $this->summariesCachingService = $summariesCachingService;
    }

    /**
     * @param ...$args
     *
     * @return mixed
     */
    public function run(...$args)
    {
        $filterValue = $args[0] ?? null;

        // clear inventory cache for the product that's being added to the cart
        if ($remoteProductId = $this->productMapRepository->getRemoteId(TypeHelper::int($filterValue, 0))) {
            $this->clearCache([$remoteProductId]);
        }

        // return the original filter input value as we do not want to alter behavior
        return $filterValue;
    }
}
