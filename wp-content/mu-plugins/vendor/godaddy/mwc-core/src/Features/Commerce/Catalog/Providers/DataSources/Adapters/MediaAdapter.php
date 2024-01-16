<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Models\Image;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ImageAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\VideoAsset;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter for converting a {@see Product} media assets into {@see AbstractAsset} objects.
 *
 * @see ImageAsset
 * @see VideoAsset
 */
class MediaAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts a {@see Product} media assets into an array of {@see AbstractAsset} objects.
     *
     * @param Product|null $product
     * @return AbstractAsset[]|ImageAsset[]|VideoAsset[]
     */
    public function convertToSource(Product $product = null) : array
    {
        // @TODO at this moment we don't support video assets yet {unfulvio 2023-03-08}
        $imageAssets = [];

        if ($product) {
            // add the main image ahead of the others from the gallery
            if ($imageAsset = $this->convertImageToSource($product->getMainImage())) {
                $imageAssets[] = $imageAsset;
            }

            foreach ($product->getImages() as $galleryImage) {
                if ($imageAsset = $this->convertImageToSource($galleryImage)) {
                    $imageAssets[] = $imageAsset;
                }
            }
        }

        return $imageAssets;
    }

    /**
     * Converts an {@see Image} into an {@see ImageAsset}.
     *
     * @param Image|null $image
     * @return ImageAsset|null
     */
    protected function convertImageToSource(?Image $image) : ?ImageAsset
    {
        if (! $image) {
            return null;
        }

        try {
            return ImageAsset::getNewInstance([
                'contentType' => 'image',
                'name'        => $image->getLabel(),
                'url'         => $image->getSize('full')->getUrl(),
                'thumbnail'   => $image->getSize('thumbnail')->getUrl(),
            ]);
        } catch (Exception $exception) {
            new SentryException($exception);
        }

        return null;
    }

    /**
     * @inerhitDoc
     */
    public function convertFromSource()
    {
        // no-op
    }
}
