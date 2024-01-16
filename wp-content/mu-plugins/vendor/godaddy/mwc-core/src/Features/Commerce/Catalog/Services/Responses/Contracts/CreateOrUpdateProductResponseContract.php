<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts;

/**
 * Contract for responses when creating or updating a product.
 */
interface CreateOrUpdateProductResponseContract
{
    /**
     * Gets the product's remote UUID.
     *
     * @return non-empty-string
     */
    public function getRemoteId() : string;
}
