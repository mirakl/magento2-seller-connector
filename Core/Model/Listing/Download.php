<?php
namespace MiraklSeller\Core\Model\Listing;

use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Listing\Download\Adapter\AdapterFactory;
use MiraklSeller\Core\Model\Listing\Download\Adapter\AdapterInterface;
use MiraklSeller\Core\Model\Listing\Export\Products;
use MiraklSeller\Core\Model\Listing\Export\ExportInterface;

class Download
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var Products
     */
    protected $exportModel;

    /**
     * @param   AdapterFactory    $adapterFactory
     * @param   Products          $exportModel;
     */
    public function __construct(
        AdapterFactory $adapterFactory,
        Products $exportModel
    ) {
        $this->adapter = $adapterFactory->create();
        $this->exportModel = $exportModel;
    }

    /**
     * @param   Listing $listing
     * @return  string
     */
    public function prepare(Listing $listing)
    {
        $products = $this->exportModel->export($listing);
        if (empty($products)) {
            return '';
        }

        foreach ($products as $data) {
            $this->adapter->write($data);
        }

        return $this->adapter->getContents();
    }

    /**
     * @return  string
     */
    public function getFileExtension()
    {
        return $this->adapter->getFileExtension();
    }

    /**
     * @return  ExportInterface
     */
    public function getExportModel()
    {
        return $this->exportModel;
    }

    /**
     * @param   ExportInterface    $exportModel
     * @return  $this
     */
    public function setExportModel(ExportInterface $exportModel)
    {
        $this->exportModel = $exportModel;

        return $this;
    }

    /**
     * @return  Download\Adapter\AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param   Download\Adapter\AdapterInterface  $adapter
     * @return  $this
     */
    public function setAdapter(Download\Adapter\AdapterInterface $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }
}
