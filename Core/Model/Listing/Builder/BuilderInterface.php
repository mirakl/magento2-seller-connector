<?php
namespace MiraklSeller\Core\Model\Listing\Builder;

use Magento\Backend\Block\Widget\Form;
use MiraklSeller\Core\Model\Listing;

interface BuilderInterface
{
    /**
     * Returns array of product ids
     *
     * @param   Listing $listing
     * @return  int[]
     */
    public function build(Listing $listing);

    /**
     * Prepare data before saving it
     *
     * @param   array   $data
     * @return  array
     */
    public function getBuilderParams($data);

    /**
     * Customizes listing's form
     *
     * @param   Form    $block
     * @param   array   $data
     * @return  $this
     */
    public function prepareForm(Form $block, &$data = []);
}