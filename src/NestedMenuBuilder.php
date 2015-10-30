<?php

namespace Heyday\SilverStripe\MenuKit;

use \DataList;
use \DataQuery;

/**
 * Class NestedMenuBuilder
 *
 * Helper for resolving a nested data structure from the flat SiteTree table
 *
 * @package Heyday\SilverStripe\MenuKit
 */
class NestedMenuBuilder
{
    /**
     * Root pages in the menu
     *
     * @var DataList
     */
    protected $rootPages;

    /**
     * All pages that should be shown in the menu
     *
     * @var DataList
     */
    protected $candidatePages;

    public function __construct(DataList $rootPages, DataList $candidatePages)
    {
        $this->rootPages = $rootPages;
        $this->candidatePages = $candidatePages;
    }

    /**
     * Return a nested array representing the menu of $this->rootPages with id, title, and urlSegment
     *
     * @return array
     */
    public function toNestedArray()
    {
        return $this->hierarchyToNestedArray($this->getIterator());
    }

    /**
     * Get an iterator to traverse the tree of records
     *
     * @return DataObjectHierarchyIterator
     * @throws \Exception
     */
    public function getIterator()
    {
        $rootPageIds = $this->rootPages->column('ID');

        // Only fetch the database columns needed to render the menu
        $candidatePages = $this->candidatePages->alterDataQuery(function(DataQuery $query) {
            $query->setQueriedColumns([
                'ID',
                'Title',
                'MenuTitle',
                'URLSegment'
            ]);
        });

        // Figure out what's in the menu
        $resolver = new DataObjectTreeResolver();
        return $resolver->getHierarchy($rootPageIds, $candidatePages);
    }

    /**
     * Convert a hierarchy of pages into the minimum data needed to create a nested menu
     *
     * @param DataObjectHierarchyIterator $iterator
     * @return array
     */
    protected function hierarchyToNestedArray(DataObjectHierarchyIterator $iterator)
    {
        $tree = array();

        while ($iterator->valid()) {
            $page = $iterator->current();
            $data = array(
                'id' => (int) $page->ID,
                'title' => $page->MenuTitle ?: $page->Title,
                'urlSegment' => $page->URLSegment
            );

            if ($iterator->hasChildren()) {
                $data['children'] = $this->hierarchyToNestedArray($iterator->getChildren());
            }

            $tree[] = $data;
            $iterator->next();
        }

        return $tree;
    }
}