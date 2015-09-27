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

        // Get ID and ParentID for all pages
        $filter = "ClassName != 'NewsPage' AND ShowInMenus = 1";
        $table = 'SiteTree' . (Versioned::current_stage() == 'Live' ? '_Live' : '');
        $sql = new SQLQuery('ID, ParentID', $table, $filter);
        $siteTree = $sql->execute()->map();

        // Collate IDs of top-level parents
        // The menu set named here should be the same as the one named in Page_Narrow_Menu.ss
        $menu = array();
        $menuSet = DataObject::get_one('MenuSet', "Name = 'MobileNav'");

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
        $ids = implode(',', $menu);
        $pages = DataObject::get('SiteTree', 'ID IN ('.$ids.')', 'Sort');
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
}
