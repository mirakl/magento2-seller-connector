<?php
namespace MiraklSeller\Process\Controller\Result;

use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magento\Framework\Controller\AbstractResult;

class Download extends AbstractResult
{
    /**
     * Additional result type
     */
    const TYPE_DOWNLOAD = 'download';

    /**
     * @var string
     */
    protected $file;

    /**
     * @param   string  $file
     * @return  $this
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function render(HttpResponseInterface $response)
    {
        /** @var \Magento\Framework\App\Response\Http $response */
        $response->clearBody();
        $response->sendHeaders();

        echo file_get_contents($this->file); // @codingStandardsIgnoreLine

        return $this;
    }
}