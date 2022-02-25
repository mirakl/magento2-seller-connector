<?php
namespace MiraklSeller\Core\Block\Adminhtml\System\Config\Button;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\Data\Form\Element\AbstractElement;

abstract class AbstractButtons extends Field implements ButtonsRendererInterface
{
    /**
     * @var array
     */
    protected $buttonsConfig = [];

    /**
     * @var bool
     */
    protected $disabled = false;

    /**
     * {@inheritdoc}
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->getButtonsHtml();
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonsHtml()
    {
        $html = '';
        foreach ($this->buttonsConfig as $buttonConfig) {
            /** @var Button $button */
            $button = $this->getLayout()->createBlock(Button::class);
            $button->setLabel(__($buttonConfig['label']))
                   ->setClass($buttonConfig['class']);

            if (isset($buttonConfig['url'])) {
                $url = $this->getUrl($buttonConfig['url'], ['_current' => true]);
                $button->setUrl($url);

                if (isset($buttonConfig['confirm'])) {
                    $confirm = __($buttonConfig['confirm']);
                    $button->setOnClick("confirmSetLocation('$confirm', '$url')");
                }
            }

            if (isset($buttonConfig['onclick'])) {
                $button->setOnClick($buttonConfig['onclick']);
            }

            if (isset($buttonConfig['config_path'])) {
                foreach ((array) $buttonConfig['config_path'] as $path) {
                    if (!$this->_scopeConfig->isSetFlag($path)) {
                        $this->setDisabled(true);
                        break;
                    }
                }
            }

            if ($this->getDisabled()) {
                $button->setDisabled(true);
            }

            $html .= $button->toHtml();
        }

        return $html;
    }

    /**
     * @return  bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * {@inheritdoc}
     */
    public function render(AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @param   bool    $flag
     * @return  $this
     */
    public function setDisabled(bool $flag)
    {
        $this->disabled = $flag;

        return $this;
    }
}
