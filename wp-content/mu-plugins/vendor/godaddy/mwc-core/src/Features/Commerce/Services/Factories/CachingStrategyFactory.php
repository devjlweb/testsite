<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Factories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingStrategyFactoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\MemoryCachingStrategy;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\MemoryWithPersistenceCachingStrategy;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\WpCacheCachingStrategy;

class CachingStrategyFactory implements CachingStrategyFactoryContract
{
    use CanGetNewInstanceTrait;

    /** @var MemoryCachingStrategy the memory caching strategy injected instance */
    protected MemoryCachingStrategy $memoryCachingStrategy;

    /** @var WpCacheCachingStrategy the WP caching strategy injected instance */
    protected WpCacheCachingStrategy $persistentCachingStrategy;

    /** @var MemoryWithPersistenceCachingStrategy the caching strategy based on memory with persistence */
    protected MemoryWithPersistenceCachingStrategy $memoryWithPersistenceCachingStrategy;

    /**
     * Constructor.
     *
     * @param MemoryCachingStrategy $memoryCachingStrategy
     * @param WpCacheCachingStrategy $persistentCachingStrategy
     */
    public function __construct(MemoryCachingStrategy $memoryCachingStrategy, WpCacheCachingStrategy $persistentCachingStrategy)
    {
        $this->memoryCachingStrategy = $memoryCachingStrategy;
        $this->persistentCachingStrategy = $persistentCachingStrategy;
    }

    /**
     * Returns caching strategy based whether the cart, checkout, or product admin pages are the current screen.
     *
     * @return CachingStrategyContract
     */
    public function makeCachingStrategy() : CachingStrategyContract
    {
        if ($this->canUsePersistentCachingStrategy()) {
            return $this->persistentCachingStrategy;
        }

        return $this->memoryWithPersistenceCachingStrategy ??= new MemoryWithPersistenceCachingStrategy(
            $this->memoryCachingStrategy,
            $this->persistentCachingStrategy
        );
    }

    /**
     * Determines if the persistent caching strategy can be used.
     *
     * @return bool
     */
    protected function canUsePersistentCachingStrategy() : bool
    {
        return ! ($this->isCartOrCheckout() || $this->isAdminProductPage());
    }

    /**
     * Determines if the cart or checkout pages are the current screen.
     *
     * @return bool
     */
    protected function isCartOrCheckout() : bool
    {
        return WooCommerceRepository::isCartPage() ||
            WooCommerceRepository::isCheckoutPage() ||
            WooCommerceRepository::isCheckoutPayPage();
    }

    /**
     * Determines if the current screen is the edit product page in admin area.
     *
     * @NOTE: This method will always return false if it's called prior to the WordPress admin_init hook.
     *
     * @return bool
     */
    protected function isAdminProductPage() : bool
    {
        $screensToCheck = ['edit-product', 'product'];

        try {
            // inside a try/catch block to safely execute in case the WordPress admin_init hook wasn't called yet
            return WordPressRepository::isAdmin() && WordPressRepository::isCurrentScreen($screensToCheck);
        } catch (Exception $e) {
            return false;
        }
    }
}
