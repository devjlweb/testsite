<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;

/**
 * Trait for converting a datetime value within a Commerce response payload to formatted date string.
 */
trait CanConvertDateTimeFromTimestampTrait
{
    /**
     * Converts a datetime value from a Commerce response to a formatted date string.
     *
     * @param array<string, mixed> $responseData
     * @param string $key
     * @return string|null
     */
    protected function convertDateTimeFromTimestamp(array $responseData, string $key) : ?string
    {
        $dateTime = TypeHelper::string(ArrayHelper::get($responseData, $key), '');

        if (empty($dateTime)) {
            return null;
        }

        try {
            return (new DateTime($dateTime))->format('Y-m-d\TH:i:s\Z');
        } catch (Exception $e) {
            return null;
        }
    }
}
