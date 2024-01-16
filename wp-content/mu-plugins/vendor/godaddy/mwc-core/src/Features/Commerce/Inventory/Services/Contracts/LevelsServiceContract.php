<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\ListLevelsByRemoteIdOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\CreateOrUpdateLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\DeleteLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\DeleteLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListLevelsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadLevelResponseContract;

interface LevelsServiceContract
{
    /**
     * Create or update a level.
     *
     * @param CreateOrUpdateLevelOperationContract $operation
     *
     * @return CreateOrUpdateLevelResponseContract
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function createOrUpdateLevel(CreateOrUpdateLevelOperationContract $operation) : CreateOrUpdateLevelResponseContract;

    /**
     * Read a level.
     *
     * @param ReadLevelOperationContract $operation
     *
     * @return ReadLevelResponseContract
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function readLevel(ReadLevelOperationContract $operation) : ReadLevelResponseContract;

    /**
     * Delete a level.
     *
     * @param DeleteLevelOperationContract $operation
     *
     * @return DeleteLevelResponseContract
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function deleteLevel(DeleteLevelOperationContract $operation) : DeleteLevelResponseContract;

    /**
     * Lists levels based on provided product ids.
     *
     * @param ListLevelsByRemoteIdOperationContract $operation
     *
     * @return ListLevelsResponseContract
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function listLevelsByRemoteProductId(ListLevelsByRemoteIdOperationContract $operation) : ListLevelsResponseContract;
}
