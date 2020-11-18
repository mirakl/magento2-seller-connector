<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Tracking;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends AbstractTracking
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @param Context   $context
     * @param Filter    $filter
     */
    public function __construct(
        Context $context,
        Filter $filter
    ) {
        $this->filter = $filter;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $messages = [];
        $refresh = false;

        // MassAction Filter search 'params/namespace' as root parameter
        $this->_request->setParams(array_merge(
            $this->_request->getParam('params', []),
            $this->_request->getParams()
        ));

        $collection = $this->filter->getCollection($this->getTrackingCollection());
        $trackingIds = $collection->getAllIds();

        if (empty($trackingIds)) {
            $messages[] = [
                'type' => 'error',
                'message' => __('Please select trackings.')
            ];
        } else {
            try {
                $this->getTrackingResource()->deleteIds($trackingIds);

                $messages[] = [
                    'type'    => 'success',
                    'message' => __('Selected trackings have been deleted successfully.')
                ];
                $refresh = true;
            } catch (\Exception $e) {
                $messages[] = [
                    'type'    => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        /** @var Json $resultLayout */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultLayout->setData(['messages' => $messages, 'refresh' => $refresh]);

        return $resultLayout;
    }
}
