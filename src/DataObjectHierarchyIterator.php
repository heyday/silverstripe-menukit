<?php

namespace Heyday\SilverStripe\MenuKit;

use \ArrayList;
use \DataObject;
use \RecursiveIterator;

class DataObjectHierarchyIterator implements RecursiveIterator
{
    /**
     * @var int[]
     */
    protected $rootIds;

    /**
     * @var ArrayList
     */
    protected $records;

    /**
     * Current position in $rootIds
     * @var int
     */
    protected $index;

    /**
     * Index of records by parent ID
     * @var array
     */
    protected $parentMap = null;

    /**
     * Index of records by ID
     * @var array
     */
    protected $idMap = null;

    public function __construct(array $rootIds, array $records)
    {
        $this->index = 0;
        $this->rootIds = $rootIds;
        $this->records = $records;
    }

    public function current()
    {
        if ($this->idMap === null) {
            $this->populateMaps();
        }

        return $this->idMap[$this->key()];
    }

    public function next()
    {
        $this->index++;
    }

    public function key()
    {
        return $this->rootIds[$this->index];
    }


    public function valid()
    {
        if ($this->idMap === null) {
            $this->populateMaps();
        }

        return isset($this->rootIds[$this->index]) && isset($this->idMap[$this->key()]);
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function hasChildren()
    {
        if ($this->parentMap === null) {
            $this->populateMaps();
        }

        return !empty($this->parentMap[$this->key()]);
    }

    public function getChildren()
    {
        if ($this->parentMap === null) {
            $this->populateMaps();
        }

        $childIds = array_map(function(DataObject $record) {
            return $record->ID;
        }, $this->parentMap[$this->key()]);

        $iterator = new DataObjectHierarchyIterator($childIds, $this->records);
        $iterator->setMaps($this->parentMap, $this->idMap);

        return $iterator;
    }

    protected function getId(DataObject $record) {
        return $record->ID;
    }

    /**
     * Set pre-enumerated maps
     *
     * Used to recurse into children without repeating calculations
     *
     * @param array $parentMap
     * @param array $idMap
     * @see getChildren
     */
    protected function setMaps(array $parentMap, array $idMap)
    {
        $this->parentMap = $parentMap;
        $this->idMap = $idMap;
    }

    /**
     * Index $this->records by ID and ParentID
     * @see getChildren
     */
    protected function populateMaps()
    {
        $this->idMap = array();
        $this->parentMap = array();

        foreach ($this->records as $record) {
            $this->idMap[$record->ID] = $record;

            if (!isset($this->parentMap[$record->ParentID])) {
                $this->parentMap[$record->ParentID] = array();
            }

            $this->parentMap[$record->ParentID][] = $record;
        }
    }
}
