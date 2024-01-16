<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomerMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\GuestCustomer;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CustomerMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\GuestCustomerMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategyFactory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

class CustomerMappingStrategyFactory extends AbstractMappingStrategyFactory
{
    /**
     * {@inheritDoc}
     */
    public function getPrimaryMappingStrategyFor(object $model) : ?CustomerMappingStrategyContract
    {
        if ($model instanceof Customer && $model->getId()) {
            return $this->getRegisteredCustomerMappingStrategy();
        }

        if ($model instanceof GuestCustomer && $model->getOrderId()) {
            return $this->getGuestCustomerMappingStrategy();
        }

        return null;
    }

    /**
     * Gets an instance of the registered customer mapping strategy.
     *
     * @return CustomerMappingStrategyContract
     */
    protected function getRegisteredCustomerMappingStrategy() : CustomerMappingStrategyContract
    {
        return new RegisteredCustomerMappingStrategy(new CustomerMapRepository($this->commerceContext));
    }

    /**
     * Gets an instance of the guest customer mapping strategy.
     *
     * @return CustomerMappingStrategyContract
     */
    protected function getGuestCustomerMappingStrategy() : CustomerMappingStrategyContract
    {
        return new GuestCustomerMappingStrategy(new GuestCustomerMapRepository($this->commerceContext));
    }

    /**
     * {@inheritDoc}
     */
    public function getSecondaryMappingStrategy() : MappingStrategyContract
    {
        return new NewCustomerMappingStrategy();
    }
}
