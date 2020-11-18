<?php
namespace MiraklSeller\Process\Model\Output;

class Cli extends AbstractOutput
{
    /**
     * {@inheritdoc}
     */
    public function display($str)
    {
        printf('%s%s', $str, PHP_EOL);
        ob_flush();

        return $this;
    }
}
