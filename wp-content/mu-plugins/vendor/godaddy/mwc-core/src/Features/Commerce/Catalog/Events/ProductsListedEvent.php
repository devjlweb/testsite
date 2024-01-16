<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;

/**
 * An event that is fired when products are listed from the remote platform.
 *
 * @method static static getNewInstance(array $productAssociations)
 */
class ProductsListedEvent implements EventContract
{
    use CanGetNewInstanceTrait;

    /** @var array<ProductAssociation> */
    public array $productAssociations;

    /**
     * Constructs the event.
     *
     * @param array<ProductAssociation> $productAssociations
     */
    public function __construct(array $productAssociations)
    {
        $this->productAssociations = $productAssociations;
    }
}
