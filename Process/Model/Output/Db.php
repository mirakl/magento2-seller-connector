<?php
namespace MiraklSeller\Process\Model\Output;

class Db extends AbstractOutput
{
    /**
     * {@inheritdoc}
     */
    public function display($str)
    {
        if ($this->process) {
            $this->process->setOutput(
                trim($this->process->getOutput() . "\n" . $str)
            );
        }

        return $this;
    }
}
