<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsFromContainerTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasCommerceCapabilitiesTrait;

/**
 * Abstract base integration class for Commerce integrations.
 */
abstract class AbstractIntegration extends AbstractFeature
{
    use HasComponentsFromContainerTrait;
    use HasCommerceCapabilitiesTrait;

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'commerce.integrations.'.static::getIntegrationName();
    }

    /**
     * Gets the name of the configuration for this integration.
     *
     * @return string
     */
    abstract protected static function getIntegrationName() : string;

    /**
     * Initializes the component.
     *
     * @throws Exception
     */
    public function load() : void
    {
        $this->loadComponents();
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, bool>
     */
    public static function getCommerceCapabilities() : array
    {
        /** @var array<string, bool> $integrationCapabilities */
        $integrationCapabilities = TypeHelper::array(static::getConfiguration('capabilities', []), []);

        // apply any globally disabled capabilities to the integration's capabilities
        array_walk($integrationCapabilities, function (&$value, $capability, $globalCapabilities) {
            $value = $value && ArrayHelper::get($globalCapabilities, $capability, $value);
        }, Commerce::getCommerceCapabilities());

        return $integrationCapabilities;
    }

    /**
     * Enable a capability by setting its config to true.
     *
     * @param string $capability
     * @return void
     */
    public static function enableCapability(string $capability) : void
    {
        Configuration::set(sprintf('features.%s.capabilities.%s', static::getName(), $capability), true);
    }

    /**
     * Disable a capability by setting its config to false.
     *
     * @param string $capability
     * @return void
     */
    public static function disableCapability(string $capability) : void
    {
        Configuration::set(sprintf('features.%s.capabilities.%s', static::getName(), $capability), false);
    }

    /**
     * Temporarily disable writes while executing the supplied callable. If an exception is thrown during the callable's
     * execution, writes will be re-enabled before the exception is re-thrown.
     *
     * @param callable $callable
     * @return mixed|void
     */
    public static function withoutWrites(callable $callable)
    {
        $writesEnabled = static::hasCommerceCapability(Commerce::CAPABILITY_WRITE);
        if ($writesEnabled) {
            static::disableCapability(Commerce::CAPABILITY_WRITE);
        }

        try {
            $response = $callable();
        } finally {
            if ($writesEnabled) {
                static::enableCapability(Commerce::CAPABILITY_WRITE);
            }
        }

        if (isset($response)) {
            return $response;
        }
    }

    /**
     * Temporarily disable reads while executing the supplied callable. If an exception is thrown during the callable's
     * execution, reads will be re-enabled before the exception is re-thrown.
     *
     * @param callable $callable
     * @return mixed|void
     */
    public static function withoutReads(callable $callable)
    {
        $readsEnabled = static::hasCommerceCapability(Commerce::CAPABILITY_READ);
        if ($readsEnabled) {
            static::disableCapability(Commerce::CAPABILITY_READ);
        }

        try {
            $response = $callable();
        } finally {
            if ($readsEnabled) {
                static::enableCapability(Commerce::CAPABILITY_READ);
            }
        }

        if (isset($response)) {
            return $response;
        }
    }
}
