<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Configuration\Contracts;

interface RuntimeConfigurationContract
{
    /**
     * Get the number of cart recovery emails currently available on this site.
     *
     * @return int
     */
    public function getNumberOfCartRecoveryEmails() : int;

    /**
     * Is the email in the given position allowed, based on the number of cart recovery emails that are currently available on this site?
     *
     * @param int $messagePosition
     * @return bool
     */
    public function isCartRecoveryEmailAllowed(int $messagePosition) : bool;
}
