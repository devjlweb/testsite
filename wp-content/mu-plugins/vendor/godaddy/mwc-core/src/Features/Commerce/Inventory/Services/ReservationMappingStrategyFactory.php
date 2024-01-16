<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ReservationMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ReservationMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategyFactory;

class ReservationMappingStrategyFactory extends AbstractMappingStrategyFactory
{
    /**
     * {@inheritDoc}
     */
    public function getPrimaryMappingStrategyFor(object $model) : ?ReservationMappingStrategyContract
    {
        if ($model instanceof LineItem && $model->getId()) {
            return ReservationMappingStrategy::getNewInstance(new ReservationMapRepository($this->commerceContext));
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function getSecondaryMappingStrategy() : ReservationMappingStrategyContract
    {
        throw new CommerceException('Secondary mapping strategy is unavailable');
    }
}
