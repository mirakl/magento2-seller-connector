<?php
namespace MiraklSeller\Api\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use MiraklSeller\Api\Helper\Data as ApiHelper;

/**
 * @method  string  getError()
 * @method  $this   setError(string $error)
 */
class Hint extends Template implements RendererInterface
{
    /**
     * @var string
     */
    protected $_template = 'MiraklSeller_Api::system/config/fieldset/hint.phtml';

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @param   Context         $context
     * @param   ApiHelper       $apiHelper
     * @param   array           $data
     */
    public function __construct(
        Context $context,
        ApiHelper $apiHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->apiHelper = $apiHelper;
    }

    /**
     * @return  string|null
     */
    public function getConnectorVersion()
    {
        $version = null;
        try {
            $version = $this->apiHelper->getVersion();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
        }

        return $version;
    }

    /**
     * @return  string
     */
    public function getVersionSDK()
    {
        return $this->apiHelper->getVersionSDK();
    }

    /**
     * Render fieldset html
     *
     * @param   AbstractElement $element
     * @return  string
     */
    public function render(AbstractElement $element)
    {
        return $this->toHtml();
    }
}
