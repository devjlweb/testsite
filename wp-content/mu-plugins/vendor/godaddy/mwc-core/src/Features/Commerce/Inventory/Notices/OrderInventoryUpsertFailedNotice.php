<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Notices;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Admin\Notices\Notice;

class OrderInventoryUpsertFailedNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $type = self::TYPE_ERROR;

    /** {@inheritdoc} */
    protected $id = 'mwc-commerce-order-inventory-upsert-failed';

    /**
     * OrderInventoryUpsertFailedNotice constructor.
     */
    public function __construct()
    {
        $this->setContent(__('Notice content TBD'));
    }
}
