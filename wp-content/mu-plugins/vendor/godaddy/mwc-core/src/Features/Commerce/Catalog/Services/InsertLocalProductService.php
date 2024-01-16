<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\AbstractIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductBaseAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\InsertLocalResourceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductLocalIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractInsertLocalResourceService;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WC_Product;

/**
 * Service class to insert a Commerce-originating product into the local database.
 */
class InsertLocalProductService extends AbstractInsertLocalResourceService
{
    /** @var ProductBaseAdapter adapter for {@see ProductBase} objects */
    protected ProductBaseAdapter $productBaseAdapter;

    /** @var class-string<AbstractIntegration> name of the integration class */
    protected string $integrationClassName = CatalogIntegration::class;

    /**
     * Constructor.
     *
     * @param ProductBaseAdapter $productBaseAdapter
     * @param ProductsMappingServiceContract $productsMappingService
     */
    public function __construct(ProductBaseAdapter $productBaseAdapter, ProductsMappingServiceContract $productsMappingService)
    {
        $this->productBaseAdapter = $productBaseAdapter;

        parent::__construct($productsMappingService);
    }

    /**
     * Inserts a local version {@see Product} of the remote resource {@see ProductBase} into the local database.
     *
     * @param ProductBase $remoteResource
     * @return Product
     * @throws InsertLocalResourceException
     */
    protected function insertLocalResource(AbstractDataObject $remoteResource) : object
    {
        try {
            $coreProduct = $this->productBaseAdapter->convertFromSource($remoteResource);
            $wooProduct = ProductAdapter::getNewInstance(new WC_Product())->convertToSource($coreProduct);
        } catch(Exception $e) {
            throw new InsertLocalResourceException('Failed to insert local product for remote product with ID '.$remoteResource->productId.': '.$e->getMessage(), $e);
        }

        $wooProduct->save();

        $localId = TypeHelper::int($wooProduct->get_id(), 0);

        if (empty($localId)) {
            throw new InsertLocalResourceException('Failed to save local resource (empty local ID).');
        }

        return $coreProduct->setId($localId);
    }

    /**
     * Gets the remote resource's UUID.
     *
     * @param ProductBase $remoteResource
     * @return string
     * @throws MissingProductRemoteIdException
     */
    protected function getRemoteResourceId(AbstractDataObject $remoteResource) : string
    {
        if (empty($remoteResource->productId)) {
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        return $remoteResource->productId;
    }

    /**
     * Gets the local resource's unique identifier.
     *
     * @param object $localResource
     * @return int
     * @throws CommerceException|MissingProductLocalIdException
     */
    protected function getLocalResourceId(object $localResource) : int
    {
        if (! $localResource instanceof Product) {
            throw new CommerceException('Local resource is expected to be a Product instance.');
        }

        if (! $localId = $localResource->getId()) {
            throw new MissingProductLocalIdException('Local Product resource is missing unique ID.');
        }

        return $localId;
    }
}
