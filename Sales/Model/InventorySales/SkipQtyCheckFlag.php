<?php
namespace MiraklSeller\Sales\Model\InventorySales;

class SkipQtyCheckFlag
{
    /**
     * @var bool
     */
    protected $skipQtyCheck = false;

    /**
     * @return  bool
     */
    public function getQtySkipQtyCheck()
    {
        return $this->skipQtyCheck;
    }

    /**
     * @param   bool    $flag
     * @return  $this
     */
    public function setSkipQtyCheck($flag)
    {
        $this->skipQtyCheck = (bool) $flag;

        return $this;
    }
}