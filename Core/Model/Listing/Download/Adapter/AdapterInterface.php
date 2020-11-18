<?php
namespace MiraklSeller\Core\Model\Listing\Download\Adapter;

interface AdapterInterface
{
    /**
     * Returns file contents
     *
     * @return  string
     */
    public function getContents();

    /**
     * @return  string
     */
    public function getFileExtension();

    /**
     * Writes data to file
     *
     * @param   array   $data
     * @return  int
     */
    public function write(array $data);
}