<?php
namespace MiraklSeller\Process\Model\Output;

class Log extends AbstractOutput
{
    /**
     * {@inheritdoc}
     */
    public function display($str)
    {
        $this->logger->info($str);

        return $this;
    }
}
