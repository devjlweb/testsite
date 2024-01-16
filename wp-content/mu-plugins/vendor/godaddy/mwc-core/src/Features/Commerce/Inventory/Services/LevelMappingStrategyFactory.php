<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use GoDaddy\WordPress\MWC\Common\Models\Products\Product;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\LevelMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategyFactory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;

class LevelMappingStrategyFactory extends AbstractMappingStrategyFactory
{
    /**
     * {@inheritDoc}
     */
    public function getPrimaryMappingStrategyFor(object $model) : ?MappingStrategyContract
    {
        if ($model instanceof Product && $model->getId()) {
            return LevelMappingStrategy::getNewInstance(new LevelMapRepository($this->commerceContext));
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @throws CommerceException
     */
    public function getSecondaryMappingStrategy() : MappingStrategyContract
    {
        throw new CommerceException('Secondary mapping strategy is unavailable');
    }
}
