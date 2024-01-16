<?php

namespace GoDaddy\WordPress\MWC\Core\Features;

/**
 * Holder class for features possible categories values.
 * TODO: switch it to ENUMS once the platform minimum requirements becomes PHP 8.x {nmolham 27-12-2021}.
 *
 * @see https://www.php.net/manual/en/language.enumerations.backed.php
 */
class Categories
{
    /** @var string */
    public const STORE_MANAGEMENT = 'Store Management';

    /** @var string */
    public const MARKETING = 'Marketing and Messaging';

    /** @var string */
    public const SHIPPING = 'Shipping';

    /** @var string */
    public const MERCHANDISING = 'Merchandising';

    /** @var string */
    public const PRODUCT_TYPE = 'Product Type';

    /** @var string */
    public const CART_CHECKOUT = 'Cart and Checkout';

    /** @var string */
    public const PAYMENTS = 'Payments';
}
