<?php
namespace MiraklSeller\Process\Model\Output;

class NullOutput extends AbstractOutput
{
    /**
     * {@inheritdoc}
     */
    public function display($str)
    {
        return $this;
    }
}
