<?php
namespace MiraklSeller\Sales\Model;

class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * @param   int $count
     * @return  $this
     */
    public function setTotalRecords($count)
    {
        $this->_totalRecords = (int) $count;

        return $this;
    }

    /**
     * @param   bool    $flag
     * @return  $this
     */
    public function setIsLoaded($flag = true)
    {
        return parent::_setIsLoaded($flag);
    }
}