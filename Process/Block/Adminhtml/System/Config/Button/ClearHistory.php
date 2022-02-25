<?php
namespace MiraklSeller\Process\Block\Adminhtml\System\Config\Button;

use MiraklSeller\Core\Block\Adminhtml\System\Config\Button\AbstractButtons;

class ClearHistory extends AbstractButtons
{
    /**
     * @var array
     */
    protected $buttonsConfig = [
        [
            'label'   => 'Clear History',
            'url'     => 'mirakl_seller/process/clearHistory',
            'confirm' => 'Are you sure? This will clear all Mirakl processes history before configured days.',
            'class'   => 'scalable',
        ]
    ];
}