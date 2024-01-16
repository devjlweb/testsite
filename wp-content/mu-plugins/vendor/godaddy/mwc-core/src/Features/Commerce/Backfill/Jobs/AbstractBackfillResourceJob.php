<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Events\BackfillJobSkippedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources\AbstractSkippedResourcesRepository;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\HasJobSettingsContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobOutcome;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobSettings;
use GoDaddy\WordPress\MWC\Core\JobQueue\Traits\HasJobSettingsTrait;
use GoDaddy\WordPress\MWC\Core\JobQueue\Traits\QueueableJobTrait;

/**
 * Abstract backfill resource job class.
 *
 * @method BatchJobSettings getJobSettings()
 */
abstract class AbstractBackfillResourceJob implements QueueableJobContract, HasJobSettingsContract
{
    use QueueableJobTrait;
    use HasJobSettingsTrait;

    /** @var AbstractSkippedResourcesRepository skipped resources repository */
    protected AbstractSkippedResourcesRepository $skippedResourcesRepository;

    /**
     * @var int Number of resources attempted to process -- this should be how many results were found in the query for this batch
     */
    protected int $attemptedResourcesCount = 0;

    /**
     * Constructor.
     *
     * @param AbstractSkippedResourcesRepository $skippedResourcesRepository
     */
    public function __construct(AbstractSkippedResourcesRepository $skippedResourcesRepository)
    {
        $this->skippedResourcesRepository = $skippedResourcesRepository;

        $this->setJobSettings($this->configureJobSettings());
    }

    /**
     * Sets the attempted resources count.
     *
     * @param int $value
     * @return AbstractBackfillResourceJob
     */
    public function setAttemptedResourcesCount(int $value) : AbstractBackfillResourceJob
    {
        $this->attemptedResourcesCount = $value;

        return $this;
    }

    /**
     * Gets the attempted resources count.
     *
     * @return int
     */
    public function getAttemptedResourcesCount() : int
    {
        return $this->attemptedResourcesCount;
    }

    /**
     * Handles the job.
     *
     * This processes the current batch of items and then handles any cleanup operations required when the entire
     * resource batch is complete.
     *
     * @return void
     * @throws Exception|WordPressDatabaseException
     */
    public function handle() : void
    {
        $outcome = $this->processBatch();

        if ($outcome->isComplete) {
            $this->purgeSkippedItems();
        } else {
            /*
             * We're not yet done processing this resource, so we'll add the current job back to the start of the chain.
             * This means that the current job will run again and we won't yet proceed to the next resource.
             */
            $this->reQueueJob();
        }

        $this->jobDone();
    }

    /**
     * Processes a single batch.
     *
     * This method handles:
     *
     *  - Querying for local resources that do not exist upstream (using the supplied {@see BatchJobSettings}).
     *  - Inserting them into the remote platform.
     *  - Updating the mapping table accordingly.
     *
     * @return BatchJobOutcome
     * @throws WordPressDatabaseException|Exception
     */
    protected function processBatch() : BatchJobOutcome
    {
        if ($this->hasWriteCapability()) {
            $localResources = $this->getLocalResources();

            if (empty($localResources)) {
                // no more records to process!
                return $this->makeOutcome();
            }

            $this->createResourcesInPlatform($localResources);
        } else {
            Events::broadcast(BackfillJobSkippedEvent::getNewInstance($this->getJobKey()));
        }

        return $this->makeOutcome();
    }

    /**
     * Queries for the local resource objects.
     *
     * @return array<mixed>|null
     */
    abstract protected function getLocalResources() : ?array;

    /**
     * Attempts to create remote resources from the local copies.
     *
     * @param array<mixed> $localResources
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function createResourcesInPlatform(array $localResources) : void
    {
        foreach ($localResources as $product) {
            $this->maybeCreateResourceInPlatform($product);
        }
    }

    /**
     * Creates a resource in the platform if it's eligible. Logs ineligible and failed items.
     *
     * @param mixed $resource
     * @return void
     * @throws WordPressDatabaseException
     */
    abstract protected function maybeCreateResourceInPlatform($resource) : void;

    /**
     * Makes a {@see BatchJobOutcome} DTO with an accurate `$isComplete` property, based on the number of items found in the current batch.
     *
     * @return BatchJobOutcome
     */
    protected function makeOutcome() : BatchJobOutcome
    {
        return BatchJobOutcome::getNewInstance([
            // we're all done if we just retrieved fewer resources than we asked for
            'isComplete' => $this->getAttemptedResourcesCount() < $this->getJobSettings()->maxPerBatch,
        ]);
    }

    /**
     * Records a resource ID as skipped so we can exclude it from queries in the next batch.
     *
     * @param int $localId
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function markLocalResourceAsSkipped(int $localId) : void
    {
        $this->skippedResourcesRepository->add($localId);
    }

    /**
     * Deletes all of the current resource type from the skipped items table. We only want to keep items in this table
     * for one full cycle of backfilling. Once we've completed a cycle, we purge the table so we can start fresh next time.
     * This ensures we don't end up with stale items in the table that are maybe eligible now.
     *
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function purgeSkippedItems() : void
    {
        $this->skippedResourcesRepository->deleteAll();
    }

    /**
     * Has write capability.
     *
     * @return bool
     */
    protected function hasWriteCapability() : bool
    {
        return CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_WRITE);
    }

    /**
     * {@inheritDoc}
     */
    public function getJobKey() : string
    {
        // @phpstan-ignore-next-line
        return static::JOB_KEY;
    }
}
