<?php
namespace MiraklSeller\Core\Block\Adminhtml\System\Config\Button;

interface ButtonsRendererInterface
{
    /**
     * @return  string
     */
    public function getButtonsHtml();

    /**
     * @return  bool
     */
    public function getDisabled();
}