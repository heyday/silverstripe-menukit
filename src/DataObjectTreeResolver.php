<?php

namespace Heyday\SilverStripe\MenuKit;

use \DataList;

/**
 * SiteTree Resolver
 *
 * Fetch large portions of the SiteTree hierarchy with minimal database calls
 */
class DataObjectTreeResolver
{
    /**
     * Parent identifier field (eg. ParentID)
     *
     * @var string
     */
    protected $relationKey;

    /**
     * @param string $relationKey
     */
    public function __construct($relationKey = 'ParentID')
    {
        $this->relationKey = $relationKey;
    }

    /**
     * Get a collection of records with relationships pre-processed to prevent n+1 queries for nested children
     *
     * @param int[] $rootIds
     * @param DataList $candidateRecords - all records that should be allowed to show in this tree
     * @return DataObjectHierarchyIterator
     */
    public function getHierarchy(array $rootIds, DataList $candidateRecords)
    {
        // Reduce the query for candidate records to only those resolvable from the root IDs
        // This reduces the data pulled out of the database to a minimum, but remains a single query
        $filteredRecords = $candidateRecords->byIDs(
            $this->resolveContainedRecords($rootIds, $candidateRecords)
        );

        return new DataObjectHierarchyIterator($rootIds, $filteredRecords->toArray());
    }

    /**
     * Return a list of record IDs (including the root records) that are descendants of the specified root IDs
     *
     * @param int[] $rootIds
     * @param DataList $candidateRecords
     * @return int[]
     */
    public function resolveContainedRecords(array $rootIds, DataList $candidateRecords)
    {
        $parentMap = $this->getRelationMap($candidateRecords);
        $containedRecords = array_flip($rootIds);

        // Loop over candidate records until no more relationships are discovered
        // This is usually O(2n), since one pass pf records sorted by ID should discover all relationships
        do {
            $continue = false;

            foreach ($parentMap as $id => $parentId) {
                // If a records's parent is in $menu, append it to $menu
                if (isset($containedRecords[$parentId]) && !isset($containedRecords[$id])) {
                    $containedRecords[$id] = true;
                    $continue = true;
                }
            }
        } while ($continue);

        return array_keys($containedRecords);
    }

    /**
     * Get a map of IDs to ParentIDs for all candidate records
     *
     * Records are sorted by ID since parents will usually be created before children.
     * This order allows the minimum number of iterations to detect contained children.
     *
     * @param DataList $candidateRecords
     * @return int[] => int
     */
    protected function getRelationMap(DataList $candidateRecords)
    {
        return $candidateRecords
            ->sort('ID ASC')
            ->map('ID', $this->relationKey)
            ->toArray();
    }
}
