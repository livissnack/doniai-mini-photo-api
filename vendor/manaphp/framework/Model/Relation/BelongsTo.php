<?php

namespace ManaPHP\Model\Relation;

use ManaPHP\Model\Relation;

class BelongsTo extends Relation
{
    /**
     * @var string
     */
    protected $_thisField;

    /**
     * @var string
     */
    protected $_thatField;

    /**
     * Relation constructor.
     *
     * @param string $thisModel
     * @param string $thisField
     * @param string $thatModel
     * @param string $thatField
     */
    public function __construct($thisModel, $thisField, $thatModel, $thatField)
    {
        $this->_thisModel = $thisModel;
        $this->_thisField = $thisField;
        $this->_thatModel = $thatModel;
        $this->_thatField = $thatField;
    }

    /**
     * @param array                   $r
     * @param \ManaPHP\QueryInterface $query
     * @param string                  $name
     * @param bool                    $asArray
     *
     * @return array
     */
    public function earlyLoad($r, $query, $name, $asArray)
    {
        $thisField = $this->_thisField;
        $thatField = $this->_thatField;

        $ids = array_values(array_unique(array_column($r, $thisField)));
        $data = $query->whereIn($thatField, $ids)->indexBy($thatField)->fetch($asArray);

        foreach ($r as $ri => $rv) {
            $key = $rv[$thisField];
            $r[$ri][$name] = $data[$key] ?? null;
        }

        return $r;
    }

    /**
     * @param \ManaPHP\Model $instance
     *
     * @return \ManaPHP\QueryInterface
     */
    public function lazyLoad($instance)
    {
        /** @var \ManaPHP\Model $thatModel */
        $thatModel = $this->_thatModel;
        $thisField = $this->_thisField;
        $thatField = $this->_thatField;

        return $thatModel::select()->whereEq($thatField, $instance->$thisField)->setFetchType(false);
    }
}
