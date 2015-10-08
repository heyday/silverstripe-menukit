<?php

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
     * Get a collection of pages with relationships pre-processed to prevent n+1 queries
     *
     * @param int[] $rootIds
     * @param DataList $candidateRecords - all records that should be allowed to show in this tree
     * @return DataObjectHierarchy
     */
    public function getHierarchy(array $rootIds, DataList $candidateRecords)
    {
        // Reduce the query for candidate records to only those resolvable from the root IDs
        // This reduces the data pulled out of the database to a minimum, but remains a single query
        $filteredRecords = $candidateRecords->filter(
            'ID', $this->resolveContainedPages($rootIds, $candidateRecords)
        );

        return new DataObjectHierarchy($rootIds, $filteredRecords->toArray());
    }

    /**
     * Return a list of page IDs that are descendants of the given page IDs
     *
     * @param int[] $rootPageIds
     * @param DataList $candidateRecords
     * @return int[]
     */
    public function resolveContainedPages(array $rootPageIds, DataList $candidateRecords)
    {
        // Iterating across all page IDs and ParentIDs, build a list containing only page IDs that will render in the menu
        // This is to avoid fetching pages we don't need
        $pageParentMap = $this->getRelationMap($candidateRecords);
        $pagesInMenu = $rootPageIds;

        // Loop over site tree until no more relationships are discovered
        // In the worst-case, this will iterate once for every level of hierarchy in the site
        do {
            $continue = false;

            foreach ($pageParentMap as $pageId => $parentId) {

                // If a page's parent is in $menu, append it to $menu
                if (in_array($parentId, $pagesInMenu)) {
                    $pagesInMenu[] = $pageId;
                    $continue = true;

                    // Remove "marked" items from the list reduce the number of iterations required
                    unset($pageParentMap[$pageId]);
                }
            }
        } while ($continue);

        return $pagesInMenu;
    }

    /**
     * Get a map of IDs to ParentIDs for all candidate records
     *
     * @param DataList $candidateRecords
     * @return int[] => int
     */
    protected function getRelationMap(DataList $candidateRecords)
    {
        return $candidateRecords->map('ID', $this->relationKey)->toArray();
    }
}
