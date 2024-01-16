<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\ProductReadHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\ProductVariationReadHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanLoadWhenReadsEnabledTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CustomWordPressCoreHook;
use WP_Post;
use WP_Query;

/**
 * Interceptor for reading product post objects from catalog.
 */
class ProductReadInterceptor extends AbstractInterceptor
{
    use CanLoadWhenReadsEnabledTrait;

    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        /* @see wp_insert_post() */
        Register::action()
            ->setGroup(CustomWordPressCoreHook::WpInsertPost_BeforeGetPostInstance)
            ->setHandler([$this, 'disableReads'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        /* @see wp_insert_post() */
        Register::action()
            ->setGroup(CustomWordPressCoreHook::WpInsertPost_AfterGetPostInstance)
            ->setHandler([$this, 'enableReads'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        /* @see WP_Post::get_instance() */
        Register::filter()
            ->setGroup(CustomWordPressCoreHook::WpPost_GetInstance)
            ->setHandler([ProductReadHandler::class, 'handle'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        /* @see WP_Query::get_posts() */
        Register::action()
            ->setGroup('pre_get_posts')
            ->setHandler([ProductVariationReadHandler::class, 'handle'])
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Enables reads from catalog.
     *
     * @return void
     */
    public function enableReads() : void
    {
        CatalogIntegration::enableCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * Disables reads from catalog.
     *
     * @return void
     */
    public function disableReads() : void
    {
        CatalogIntegration::disableCapability(Commerce::CAPABILITY_READ);
    }
}
