<?php

class MobileMenuExtension extends DataExtension
{
        /**
     * Return a flat list of pages that are descendants of pages in the MobileNav menu set
     *
     * @return array|SiteTree
     */
    public function getNavigationTree()
    {
        if (isset($this->_cachedNavigationTree)) {
            return $this->_cachedNavigationTree;
        }

        $siteTree = $this->getMenuPageIDs();

        // Collate IDs of top-level parents
        $menu = array();
        $menuSet = MenuSet::get()->filter('Name', $this->stat('source_menu_set'));

        if (!$menuSet) {
            return null;
        }

        foreach ($menuSet->MenuItems() as $item) {
            $menu[] = $item->PageID;
        }

        // Loop over site tree until no more relationships are discovered
        while (true) {

            $changed = false;
            foreach ($siteTree as $pageId => &$parentId) {

                if ($parentId !== null && in_array($parentId, $menu)) {
                    $menu[] = $pageId;
                    $parentId = null;
                    $changed = true;
                }

            }

            if (!$changed) {
                break;
            }
        }

        // Fetch actual pages
        $pages = SiteTree::get()->filter('ID', $menu);
        return $this->_cachedNavigationTree = $pages->toArray();
    }

    /**
     * Return the navigation tree with relationships pre-processed in properties
     *
     * Pages are appended with 'HasChildren' (bool) and 'MenuChildren' (ArrayList) properties
     * that remove the need to call ->Children() repeatedly.
     *
     * @see getNavigationTree
     * @return ArrayList
     */
    public function getNavigationTreeOptimised()
    {
        $tree = new ArrayList($this->getNavigationTree());

        // Group pages by ParentID
        $parentMap = array();
        $idMap = array();
        foreach ($tree as $index => &$page) {
            if (!isset($parentMap[$page->ParentID])) {
                $parentMap[$page->ParentID] = array();
            }

            $idMap[$page->ID] = $page;
            $parentMap[$page->ParentID][] = $page;
        }

        // Add child and parent information to each page
        foreach ($tree as $page) {

            if ($page->ParentID) {
                $page->MenuParent = $idMap[$page->ParentID];
            }

            if ($page->HasChildren = isset($parentMap[$page->ID])) {
                $page->MenuChildren = new ArrayList($parentMap[$page->ID]);
            }
        }

        return $tree;
    }

    /**
     * Get a map of page IDs to ParentIDs for all candidate pages
     *
     * @return int[] => int
     */
    protected function getMenuPageIDs()
    {
        return SiteTree::get()
            ->exclude('ClassName', $this->stat('exclude_pagetypes'))
            ->map('ID, ParentID');
    }
}
