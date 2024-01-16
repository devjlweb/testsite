<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\ProductQueryHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanDetermineWpQueryProductPostTypeTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CustomWordPressCoreHook;
use WP_Query;

/**
 * Intercepts {@see WP_Query} to inject Commerce data in product posts.
 */
class WpQueryInterceptor extends AbstractInterceptor
{
    use CanDetermineWpQueryProductPostTypeTrait;

    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('posts_results')
            ->setHandler([ProductQueryHandler::class, 'handle'])
            ->setArgumentsCount(2)
            ->execute();

        /* @see WP_Query::get_posts() */
        Register::filter()
            ->setGroup(CustomWordPressCoreHook::WpQuery_BeforeGetPost)
            ->setHandler([$this, 'primePostsCache'])
            ->setArgumentsCount(2)
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::action()
            ->setGroup('pre_get_posts')
            ->setHandler([$this, 'maybeDisableFilterSuppression'])
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Disables the `suppress_filters` option for product queries.
     *
     * @internal
     *
     * @param WP_Query|mixed $wpQuery passed by reference
     * @return void
     */
    public function maybeDisableFilterSuppression($wpQuery) : void
    {
        if ($wpQuery instanceof WP_Query && $this->isProductQuery($wpQuery)) {
            $wpQuery->query_vars['suppress_filters'] = false;
        }
    }

    /**
     * Pre-primes the posts cache before {@see WP_Query::get_posts()} loops posts with {@see get_post()} through `array_map`.
     *
     * @internal
     *
     * @param int[]|object[]|mixed $posts
     * @param WP_Query|mixed $wpQuery WP_Query instance
     * @return int[]|mixed primed post IDs
     */
    public function primePostsCache($posts, $wpQuery)
    {
        if (! is_array($posts) || ! ($wpQuery instanceof WP_Query && $this->isProductQuery($wpQuery))) {
            return $posts;
        }

        // If array of post-like objects, try to get the IDs.
        if (is_object(reset($posts))) {
            $postIds = array_map(function ($post) {
                return $post->ID;
            }, $posts);
        } else {
            $postIds = $posts;
        }

        /* prime the cache for {@see get_post()} to pull from */
        _prime_post_caches($postIds);

        return $posts;
    }
}
