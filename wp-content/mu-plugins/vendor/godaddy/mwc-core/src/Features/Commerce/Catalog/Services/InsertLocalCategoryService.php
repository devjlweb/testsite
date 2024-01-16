<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomies\Terms\Exceptions\TermCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomies\Terms\Exceptions\TermReadFailedException;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomies\Terms\Exceptions\TermUpdateFailedException;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\AbstractIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\CategoryAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\InsertLocalResourceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryLocalIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractInsertLocalResourceService;

/**
 * Service class to insert a Commerce-originating category into the local database.
 */
class InsertLocalCategoryService extends AbstractInsertLocalResourceService
{
    /** @var CategoryAdapter */
    protected CategoryAdapter $categoryAdapter;

    /** @var class-string<AbstractIntegration> name of the integration class */
    protected string $integrationClassName = CatalogIntegration::class;

    /**
     * Service constructor.
     *
     * @param CategoriesMappingServiceContract $mappingService
     * @param CategoryAdapter $categoryAdapter
     */
    public function __construct(CategoriesMappingServiceContract $mappingService, CategoryAdapter $categoryAdapter)
    {
        $this->categoryAdapter = $categoryAdapter;

        parent::__construct($mappingService);
    }

    /**
     * Inserts a local version (@see Term} of the remote resource {@see Category} into the local database.
     *
     * @param Category $remoteResource
     * @return Term
     * @throws InsertLocalResourceException
     */
    protected function insertLocalResource(AbstractDataObject $remoteResource) : object
    {
        try {
            $term = $this->categoryAdapter->convertFromSource($remoteResource);

            /** @var Term $term */
            $term = CatalogIntegration::withoutReads(fn () => $term->save()); // `wp_insert_term()` triggers several `get_terms()` calls, which leads to a read in the API

            return $term;
        } catch(TermCreateFailedException|TermUpdateFailedException $e) {
            throw new InsertLocalResourceException('Failed to insert the local category.', $e);
        } catch(TermReadFailedException $e) {
            throw new InsertLocalResourceException('Failed to read the local category after successful insertion or update.', $e);
        } catch(Exception $e) {
            throw new InsertLocalResourceException('Failed to convert remote category to local object.', $e);
        }
    }

    /**
     * Gets the remote resource's UUID.
     *
     * @param Category $remoteResource
     * @return string
     * @throws MissingCategoryRemoteIdException
     */
    protected function getRemoteResourceId(AbstractDataObject $remoteResource) : string
    {
        if (empty($remoteResource->categoryId)) {
            throw MissingCategoryRemoteIdException::withDefaultMessage();
        }

        return $remoteResource->categoryId;
    }

    /**
     * Gets the local resource's unique identifier.
     *
     * @param object $localResource
     * @return int
     * @throws CommerceException|MissingCategoryLocalIdException
     */
    protected function getLocalResourceId(object $localResource) : int
    {
        if (! $localResource instanceof Term) {
            throw new CommerceException('Local resource is expected to be a Category instance.');
        }

        if (! $localId = $localResource->getId()) {
            throw new MissingCategoryLocalIdException('Local Category resource is missing unique ID.');
        }

        return $localId;
    }
}
