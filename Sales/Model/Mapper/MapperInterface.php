<?php
namespace MiraklSeller\Sales\Model\Mapper;

interface MapperInterface
{
    /**
     * @param   array       $data
     * @param   string|null $locale
     * @return  array
     */
    public function map(array $data, $locale = null);
}