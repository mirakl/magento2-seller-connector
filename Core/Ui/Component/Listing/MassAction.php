<?php
namespace MiraklSeller\Core\Ui\Component\Listing;

class MassAction extends \Magento\Ui\Component\MassAction
{
    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        parent::prepare();

        $config = $this->getData('config');
        foreach ($config['actions'] as & $action) {
            $action['url'] = sprintf(
                '%slisting_id/%s/',
                $action['url'],
                $this->context->getRequestParam('listing_id')
            );
        }

        $this->setData('config', $config);
    }
}