<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItemDetails;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;

/**
 * Adapts a LineItem object to, and from a LineItemDetails object.
 */
class LineItemDetailsAdapter implements DataObjectAdapterContract
{
    /**
     * @param ?LineItemDetails $source
     * @param ?LineItem $lineItem
     *
     * @return LineItem
     *
     * {@inheritDoc}
     */
    public function convertFromSource($source, ?LineItem $lineItem = null) : LineItem
    {
        $lineItem ??= new LineItem();

        if (! $source || ! $source->sku) {
            return $lineItem;
        }

        return $lineItem->setSku($source->sku);
    }

    /**
     * @param LineItem $target
     *
     * @return LineItemDetails
     *
     * {@inheritDoc}
     */
    public function convertToSource($target) : LineItemDetails
    {
        $data = [];

        if ($sku = $target->getSku()) {
            $data['sku'] = $sku;
        }

        return new LineItemDetails($data);
    }
}
