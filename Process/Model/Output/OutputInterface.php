<?php
namespace MiraklSeller\Process\Model\Output;

interface OutputInterface
{
    /**
     * @return  $this
     */
    public function close();

    /**
     * @param   string  $str
     * @return  $this
     */
    public function display($str);

    /**
     * @return  string
     */
    public function getType();
}