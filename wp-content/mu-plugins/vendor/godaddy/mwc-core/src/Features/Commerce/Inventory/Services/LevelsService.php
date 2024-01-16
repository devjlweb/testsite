<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingLevelRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\ListLevelsByRemoteIdOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListLevelsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadLevelInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertLevelInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataSources\Adapters\LevelAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\CreateOrUpdateLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\DeleteLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\DeleteLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListLevelsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\CreateOrUpdateLevelResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ListLevelsResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ReadLevelResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class LevelsService implements LevelsServiceContract
{
    protected CommerceContextContract $commerceContext;
    protected InventoryProviderContract $provider;
    protected LevelMappingServiceContract $levelMappingService;
    protected LocationMappingServiceContract $locationMappingService;
    protected ProductsMappingServiceContract $productMappingService;

    /**
     * @param CommerceContextContract $commerceContext
     * @param InventoryProviderContract $provider
     * @param LevelMappingServiceContract $levelMappingService
     * @param LocationMappingServiceContract $locationMappingService
     * @param ProductsMappingServiceContract $productMappingService
     */
    public function __construct(
        CommerceContextContract $commerceContext,
        InventoryProviderContract $provider,
        LevelMappingServiceContract $levelMappingService,
        LocationMappingServiceContract $locationMappingService,
        ProductsMappingServiceContract $productMappingService
    ) {
        $this->commerceContext = $commerceContext;
        $this->provider = $provider;
        $this->levelMappingService = $levelMappingService;
        $this->locationMappingService = $locationMappingService;
        $this->productMappingService = $productMappingService;
    }

    /**
     * {@inheritDoc}
     */
    public function createOrUpdateLevel(CreateOrUpdateLevelOperationContract $operation) : CreateOrUpdateLevelResponseContract
    {
        $product = $operation->getProduct();

        $existingRemoteId = $this->levelMappingService->getRemoteId($product);

        // create or update in the inventory service
        $level = $this->provider->levels()->createOrUpdate(
            $this->getUpsertLevelInput($product, $existingRemoteId)
        );

        if (! $level->inventoryLevelId) {
            throw MissingLevelRemoteIdException::withDefaultMessage();
        }

        // save the remote ID if not done already
        if (! $existingRemoteId) {
            $this->levelMappingService->saveRemoteId($product, $level->inventoryLevelId);
        }

        return new CreateOrUpdateLevelResponse($level);
    }

    /**
     * Gets the upsert level input.
     *
     * @param Product $product
     * @param string|null $existingRemoteId
     *
     * @return UpsertLevelInput
     * @throws MissingProductRemoteIdException
     */
    protected function getUpsertLevelInput(Product $product, ?string $existingRemoteId) : UpsertLevelInput
    {
        return new UpsertLevelInput([
            'storeId' => $this->commerceContext->getStoreId(),
            'level'   => $this->buildLevelData($product, $existingRemoteId),
        ]);
    }

    /**
     * Builds a level from the given product.
     *
     * @param Product $product
     * @param string|null $remoteId
     *
     * @return Level
     * @throws MissingProductRemoteIdException
     */
    protected function buildLevelData(Product $product, ?string $remoteId) : Level
    {
        /** @var Level $level */
        $level = LevelAdapter::getNewInstance()->convertToSource($product);
        $locationId = $this->locationMappingService->getRemoteId() ?? null;
        $productId = $this->productMappingService->getRemoteId($product);

        if (! $productId) {
            throw new MissingProductRemoteIdException('The level product has no remote UUID saved');
        }

        $level->inventoryLevelId = $remoteId;
        $level->inventoryLocationId = $locationId;
        $level->productId = $productId;

        return $level;
    }

    /**
     * {@inheritDoc}
     */
    public function readLevel(ReadLevelOperationContract $operation) : ReadLevelResponseContract
    {
        $product = $operation->getProduct();

        if (! $existingRemoteLevelId = $this->levelMappingService->getRemoteId($product)) {
            throw new MissingLevelRemoteIdException('Could not get the remote level ID for given product');
        }

        $level = $this->provider->levels()->read(ReadLevelInput::getNewInstance([
            'storeId' => $this->commerceContext->getStoreId(),
            'levelId' => $existingRemoteLevelId,
        ]));

        return new ReadLevelResponse($level);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteLevel(DeleteLevelOperationContract $operation) : DeleteLevelResponseContract
    {
        // @TODO: Implement this test on MWC-10823 {acastro1 2023.03.06}
        /* @phpstan-ignore-next-line */
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function listLevelsByRemoteProductId(ListLevelsByRemoteIdOperationContract $operation) : ListLevelsResponseContract
    {
        $levels = $this->provider->levels()->list($this->getListLevelsInput(ArrayHelper::wrap($operation->getIds())));

        return new ListLevelsResponse($levels);
    }

    /**
     * @param string[] $productIds
     *
     * @return ListLevelsInput
     */
    protected function getListLevelsInput(array $productIds) : ListLevelsInput
    {
        return ListLevelsInput::getNewInstance([
            'storeId'    => $this->commerceContext->getStoreId(),
            'productIds' => $productIds,
        ]);
    }
}
