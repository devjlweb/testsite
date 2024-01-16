<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListProductsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ListProductsServiceContract;

/**
 * Service class to aid in listing products by ID in batches.
 *
 * @method ProductAssociation[] batchListByLocalIds(array $localIds)
 */
class BatchListProductsByLocalIdService extends AbstractBatchListResourcesByLocalIdService
{
    /** @var ListProductsServiceContract service to list products */
    protected ListProductsServiceContract $listProductsService;

    /**
     * Constructor.
     *
     * @param ListProductsServiceContract $listProductsService
     */
    public function __construct(ListProductsServiceContract $listProductsService)
    {
        $this->listProductsService = $listProductsService;
    }

    /**
     * {@inheritDoc}
     * @return ProductAssociation[]
     */
    protected function listBatch(array $localIds) : array
    {
        return $this->listProductsService->list(
            ListProductsOperation::seed(['localIds' => $localIds])
        );
    }
}
