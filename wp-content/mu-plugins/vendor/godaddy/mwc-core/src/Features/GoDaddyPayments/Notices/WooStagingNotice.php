<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Admin\Notices\Notice;

class WooStagingNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = true;

    /** {@inheritdoc} */
    protected $type = self::TYPE_WARNING;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-staging';

    public function __construct()
    {
        $this->setContent(__('WooCommerce charges or authorizations/captures as well as refunds and voids made in your Staging site will process normally in your GoDaddy Payments account.', 'mwc-core'));
    }
}
