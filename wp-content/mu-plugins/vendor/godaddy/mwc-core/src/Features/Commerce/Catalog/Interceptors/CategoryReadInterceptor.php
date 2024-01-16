<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterFilter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\CategoryReadHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\PrimeTermCachesHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\TermQueryHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CustomWordPressCoreHook;
use WP_Term_Query;

/**
 * Interceptor to register hooks for facilitating reading a single category.
 */
class CategoryReadInterceptor extends AbstractInterceptor
{
    /**
     * Registers hooks.
     *
     * @see get_term()
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        $this->getGetProductCatHook()
            ->execute();

        Register::action()
            ->setGroup('pre_get_terms')
            ->setHandler([$this, 'disableGetProductCatFilter'])
            ->execute();

        /* @see get_terms() */
        Register::filter()
            ->setGroup('get_terms')
            ->setHandler([$this, 'filterTermQuery'])
            ->setArgumentsCount(4)
            ->execute();

        Register::filter()
            ->setGroup(CustomWordPressCoreHook::PrimeTermCaches_Terms)
            ->setHandler([PrimeTermCachesHandler::class, 'handle'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Gets the filter for `get_product_cat`.
     *
     * This allows us to modify a single term that's retrieved by its ID.
     *
     * @see get_term()
     *
     * @return RegisterFilter
     */
    protected function getGetProductCatHook() : RegisterFilter
    {
        return Register::filter()
            ->setGroup('get_product_cat') // `get_{taxonomy}` dynamic hook
            ->setHandler([CategoryReadHandler::class, 'handle'])
            ->setArgumentsCount(2)
            ->setPriority(PHP_INT_MAX);
    }

    /**
     * De-registers the {@see static::getGetProductCatHook()} filter.
     *
     * We do this because `get_term()` is called on all {@see WP_Term_Query} results before the `get_terms` filter fires.
     * So, unless we deregister this, it will trigger our {@see static::getGetProductCatHook()} code first,
     * which will do a single API request to read each category, thus resulting in N+1 issues!
     *
     * @param WP_Term_Query|mixed $query
     * @return void
     */
    public function disableGetProductCatFilter($query)
    {
        if (! $query instanceof WP_Term_Query || ! $this->isProductCategoryTaxonomyQuery($query)) {
            return;
        }

        try {
            // disable the `get_term()` filter -- we'll re-enable it later!
            $this->getGetProductCatHook()->deregister();
        } catch(Exception $exception) {
            // catch in hook callback
        }
    }

    /**
     * Determines whether the supplied {@see WP_Term_Query} is for product categories.
     *
     * @param WP_Term_Query $query
     * @return bool
     */
    protected function isProductCategoryTaxonomyQuery(WP_Term_Query $query) : bool
    {
        $taxonomies = ArrayHelper::wrap(ArrayHelper::get($query->query_vars, 'taxonomy', []));

        return in_array(CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY, $taxonomies, true);
    }

    /**
     * Filters the found terms {@see get_terms()}.
     *
     * @param array<mixed>|mixed $terms
     * @param string[]|null|mixed $taxonomies
     * @param array<string, mixed>|mixed $args
     * @param WP_Term_Query|mixed $termQuery
     * @return mixed|null
     */
    public function filterTermQuery($terms, $taxonomies, $args, $termQuery)
    {
        if (! $termQuery instanceof WP_Term_Query || ! $this->isProductCategoryTaxonomyQuery($termQuery)) {
            return $terms;
        }

        $terms = TermQueryHandler::handle($terms, $taxonomies, $args, $termQuery);

        try {
            // re-enable the filter on `get_term()`
            $this->getGetProductCatHook()->execute();
        } catch(Exception $e) {
            // catch exceptions in hook callbacks
        }

        return $terms;
    }
}
