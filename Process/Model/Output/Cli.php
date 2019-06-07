<?php
namespace MiraklSeller\Process\Model\Output;

class Cli extends AbstractOutput
{
    /**
     * {@inheritdoc}
     */
    public function display($str)
    {
        echo $str . PHP_EOL; // @codingStandardsIgnoreLine
        @ob_flush();

        return $this;
    }
}
