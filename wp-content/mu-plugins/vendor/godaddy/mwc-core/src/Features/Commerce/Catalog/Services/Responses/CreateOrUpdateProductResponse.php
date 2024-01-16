<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\CreateOrUpdateProductResponseContract;

/**
 * Response object for a create or update product request.
 *
 * @method static static getNewInstance(ProductBase $product)
 */
class CreateOrUpdateProductResponse implements CreateOrUpdateProductResponseContract
{
    use CanGetNewInstanceTrait;

    /**
     * @var non-empty-string
     */
    protected string $remoteId;

    /**
     * @param non-empty-string $remoteId
     */
    public function __construct(string $remoteId)
    {
        $this->remoteId = $remoteId;
    }

    /**
     * {@inheritDoc}
     */
    public function getRemoteId() : string
    {
        return $this->remoteId;
    }
}
