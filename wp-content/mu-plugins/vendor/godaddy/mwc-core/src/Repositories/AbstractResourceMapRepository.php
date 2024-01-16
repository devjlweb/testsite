<?php

namespace GoDaddy\WordPress\MWC\Core\Repositories;

use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTableColumns;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMap;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMapCollection;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\AbstractResourceRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CommerceContextRepository;

/**
 * Abstract resource map repository.
 *
 * @phpstan-type TResourceMapRow array{id: numeric-string, commerce_id: string, local_id: numeric-string}
 */
abstract class AbstractResourceMapRepository extends AbstractResourceRepository
{
    /** @var string commerce map IDs (uuids, ksuids) table name */
    public const MAP_IDS_TABLE = 'godaddy_mwc_commerce_map_ids';

    /** @var string commerce resource type table name */
    public const RESOURCE_TYPES_TABLE = 'godaddy_mwc_commerce_map_resource_types';

    /** @var string column storing the remote commerce IDs */
    public const COLUMN_COMMERCE_ID = 'commerce_id';

    /** @var string column storing the primary ID of the map's row */
    public const COLUMN_ID = 'id';

    /** @var string column storing the local IDs */
    public const COLUMN_LOCAL_ID = 'local_id';

    /** @var string column storing the resource type IDs */
    public const COLUMN_RESOURCE_TYPE_ID = 'resource_type_id';

    /** @var string column storing the commerce context IDs */
    public const COLUMN_COMMERCE_CONTEXT_ID = 'commerce_context_id';

    /**
     * Adds a new map to associate the local ID with the given remote UUID.
     *
     * @param int $localId
     * @param string $remoteId
     * @return void
     * @throws WordPressDatabaseException
     */
    public function add(int $localId, string $remoteId) : void
    {
        DatabaseRepository::insert(static::MAP_IDS_TABLE, [
            static::COLUMN_LOCAL_ID            => $localId,
            static::COLUMN_COMMERCE_ID         => $this->remoteIdMutationStrategy->getRemoteIdForDatabase($remoteId),
            static::COLUMN_RESOURCE_TYPE_ID    => $this->getResourceTypeId(),
            static::COLUMN_COMMERCE_CONTEXT_ID => $this->getContextId(),
        ]);
    }

    /**
     * Updates the remote ID of a row, if found by local ID, otherwise adds the map.
     *
     * Unlike {@see AbstractResourceMapRepository::add()}, this method does not attempt
     * to write to the database if an identical map already exists.
     *
     * @throws WordPressDatabaseException
     */
    public function addOrUpdateRemoteId(int $localId, string $remoteId) : void
    {
        $existingResourceMap = $this->getMappingByLocalId($localId);

        if (! $existingResourceMap) {
            $this->add($localId, $remoteId);
        } elseif ($remoteId !== $existingResourceMap->commerceId) {
            $formattedRemoteId = $this->remoteIdMutationStrategy->getRemoteIdForDatabase($remoteId);

            DatabaseRepository::update(
                AbstractResourceMapRepository::MAP_IDS_TABLE,
                [AbstractResourceMapRepository::COLUMN_COMMERCE_ID => $formattedRemoteId],
                [AbstractResourceMapRepository::COLUMN_ID          => $existingResourceMap->id],
                ['%s'],
                ['%d'],
            );
        }
    }

    /**
     * Finds the remote ID of a resource by its local ID.
     *
     * @param int $localId
     * @return string|null
     */
    public function getRemoteId(int $localId) : ?string
    {
        $uuidMapTableName = static::MAP_IDS_TABLE;

        $row = DatabaseRepository::getRow(
            implode(' ', [
                'SELECT map_ids.'.static::COLUMN_COMMERCE_ID." FROM {$uuidMapTableName} AS map_ids",
                $this->getResourceTypeJoinClause(),
                $this->getContextJoinClause(),
                'WHERE map_ids.'.static::COLUMN_LOCAL_ID.' = %d',
            ]),
            [$localId]
        );

        $result = TypeHelper::string(ArrayHelper::get($row, static::COLUMN_COMMERCE_ID), '') ?: null;

        return $this->remoteIdMutationStrategy->formatRemoteIdFromDatabase($result);
    }

    /**
     * Get a collection of resource maps by the given local IDs.
     *
     * @param int[] $localIds
     *
     * @return ResourceMapCollection
     */
    public function getMappingsByLocalIds(array $localIds) : ResourceMapCollection
    {
        return ResourceMapCollection::fromRows($this->queryRowsByIds(self::COLUMN_LOCAL_ID, $localIds));
    }

    /**
     * Get a collection of resource maps by the given remote IDs.
     *
     * @param string[] $remoteIds
     *
     * @return ResourceMapCollection
     */
    public function getMappingsByRemoteIds(array $remoteIds) : ResourceMapCollection
    {
        return ResourceMapCollection::fromRows(
            $this->queryRowsByIds(
                self::COLUMN_COMMERCE_ID,
                array_map([$this->remoteIdMutationStrategy, 'getRemoteIdForDatabase'], $remoteIds)
            )
        );
    }

    /**
     * Finds the local ID of a resource by its remote UUID.
     *
     * @param string $remoteId
     *
     * @return int|null
     */
    public function getLocalId(string $remoteId) : ?int
    {
        $uuidMapTableName = static::MAP_IDS_TABLE;

        $row = DatabaseRepository::getRow(
            implode(' ', [
                'SELECT map_ids.'.static::COLUMN_LOCAL_ID." FROM {$uuidMapTableName} AS map_ids",
                $this->getResourceTypeJoinClause(),
                $this->getContextJoinClause(),
                'WHERE map_ids.'.static::COLUMN_COMMERCE_ID.' = %s',
            ]),
            [$this->remoteIdMutationStrategy->getRemoteIdForDatabase($remoteId)]
        );

        return TypeHelper::int(ArrayHelper::get($row, static::COLUMN_LOCAL_ID), 0) ?: null;
    }

    /**
     * Gets a SQL clause that can be used to perform an inner join on the contexts table.
     *
     * @param string $idMapTableNameAlias
     * @param string $contextsTableNameAlias
     * @return string
     */
    protected function getContextJoinClause(
        string $idMapTableNameAlias = 'map_ids',
        string $contextsTableNameAlias = 'contexts'
    ) : string {
        $contextsTableName = CommerceContextRepository::CONTEXT_TABLE;
        $storeId = TypeHelper::string(esc_sql($this->commerceContext->getStoreId()), '');

        return "INNER JOIN {$contextsTableName} AS {$contextsTableNameAlias}
        ON {$contextsTableNameAlias}.id = {$idMapTableNameAlias}.".static::COLUMN_COMMERCE_CONTEXT_ID."
        AND {$contextsTableNameAlias}.gd_store_id = '{$storeId}'";
    }

    /**
     * Gets the context ID.
     *
     * @return int|null
     */
    protected function getContextId() : ?int
    {
        return $this->commerceContext->getId();
    }

    /**
     * Gets the map of the given resource local ID to a remote ID.
     */
    protected function getMappingByLocalId(int $localId) : ?ResourceMap
    {
        if (! $row = $this->getMappingRowByLocalId($localId)) {
            return null;
        }

        $row['commerce_id'] = TypeHelper::string($this->remoteIdMutationStrategy->formatRemoteIdFromDatabase($row['commerce_id']), '');

        return ResourceMap::fromRow($row);
    }

    /**
     * Gets the row representing a map of the given resource local ID to a remote ID.
     *
     * @return TResourceMapRow|array{}
     */
    protected function getMappingRowByLocalId(int $localId) : array
    {
        /** @var TResourceMapRow|array{} $row */
        $row = DatabaseRepository::getRow(
            implode(' ', [
                'SELECT map_ids.'.static::COLUMN_ID.', map_ids.'.static::COLUMN_COMMERCE_ID.', map_ids.'.static::COLUMN_LOCAL_ID.' FROM '.static::MAP_IDS_TABLE.' AS map_ids',
                $this->getResourceTypeJoinClause(),
                $this->getContextJoinClause(),
                'WHERE map_ids.'.static::COLUMN_LOCAL_ID.' = %d',
            ]),
            [$localId]
        );

        return $row;
    }

    /**
     * Get a printf-compatible placeholder for the given column name.
     *
     * @param static::COLUMN_* $columnName
     *
     * @return ($columnName is static::COLUMN_COMMERCE_ID ? '%s' : '%d')
     */
    protected function getPlaceholderForColumn(string $columnName) : string
    {
        if (static::COLUMN_COMMERCE_ID === $columnName) {
            return '%s';
        }

        return '%d';
    }

    /**
     * Query the database to select rows where the given column matches any of the values.
     *
     * @param static::COLUMN_COMMERCE_ID|static::COLUMN_LOCAL_ID $columnName
     * @param array<int|string> $values
     * @return TResourceMapRow[]
     */
    protected function queryRowsByIds(string $columnName, array $values) : array
    {
        if (! $values) {
            return [];
        }

        /** @var TResourceMapRow[] $results */
        $results = DatabaseRepository::getResults($this->getQueryRowsByIdsSql($columnName, $values), $values);

        return array_map([$this, 'formatRemoteIdFromDatabase'], $results);
    }

    /**
     * Gets the SQL necessary to query the database to select rows where the given column matches any of the values.
     *
     * @param static::COLUMN_COMMERCE_ID|static::COLUMN_LOCAL_ID $columnName
     * @param non-empty-array<int|string> $values
     * @return non-empty-string
     */
    protected function getQueryRowsByIdsSql(string $columnName, array $values) : string
    {
        $idPlaceholders = implode(',', array_fill(0, count($values), $this->getPlaceholderForColumn($columnName)));

        return implode(' ', [
            'SELECT map_ids.'.static::COLUMN_ID.', map_ids.'.static::COLUMN_COMMERCE_ID.', map_ids.'.static::COLUMN_LOCAL_ID.' FROM '.static::MAP_IDS_TABLE.' AS map_ids',
            $this->getResourceTypeJoinClause(),
            $this->getContextJoinClause(),
            "WHERE map_ids.{$columnName} IN ({$idPlaceholders})",
        ]);
    }

    /**
     * Formats the remote ID of the given row.
     *
     * @param TResourceMapRow $row
     * @return TResourceMapRow
     */
    protected function formatRemoteIdFromDatabase(array $row) : array
    {
        $row['commerce_id'] = (string) $this->remoteIdMutationStrategy->formatRemoteIdFromDatabase($row['commerce_id']);

        return $row;
    }

    /**
     * Deletes a mapping row by the provided local ID.
     *
     * @param int $localId
     * @return int number of records that were deleted
     * @throws WordPressDatabaseException
     */
    public function deleteByLocalId(int $localId) : int
    {
        $mapping = $this->getMappingByLocalId($localId);

        if ($mapping) {
            $numberRowsDeleted = DatabaseRepository::delete(
                static::MAP_IDS_TABLE,
                ['id' => $mapping->id],
                ['%d']
            );
        } else {
            $numberRowsDeleted = 0;
        }

        return TypeHelper::int($numberRowsDeleted, 0);
    }

    /**
     * Gets a SQL query that can be used to select all `local_id` values from the table for a specific resource type ID.
     * e.g. `SELECT local_id FROM godaddy_mwc_commerce_map_ids WHERE resource_type_id = 11`.
     *
     * @return string
     */
    protected function getMappedLocalIdsForResourceTypeQuery() : string
    {
        return '
            SELECT '.CommerceTableColumns::LocalId.'
            FROM '.CommerceTables::ResourceMap.'
            WHERE '.CommerceTableColumns::ResourceTypeId.' = %d
        ';
    }
}
