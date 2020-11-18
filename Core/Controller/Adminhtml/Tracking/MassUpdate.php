<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Tracking;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use MiraklSeller\Core\Helper\Tracking as TrackingHelper;

class MassUpdate extends AbstractTracking
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var TrackingHelper
     */
    protected $trackingHelper;

    /**
     * @param Context           $context
     * @param Filter            $filter
     * @param TrackingHelper    $trackingHelper
     */
    public function __construct(
        Context $context,
        Filter $filter,
        TrackingHelper $trackingHelper
    ) {
        $this->filter = $filter;
        $this->trackingHelper = $trackingHelper;
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
                $this->trackingHelper->updateTrackingsByType(
                    $trackingIds,
                    $this->getTrackingType()
                );

                $messages[] = [
                    'type' => 'success',
                    'message' => __('Selected trackings will be updated asynchronously.')
                ];
                $messages[] = [
                    'type' => 'success',
                    'message' => __(
                        'Click <a href="%1">here</a> to view process output.',
                        $this->getUrl('*/process/index')
                    )
                ];
                $refresh = true;
            } catch (\Exception $e) {
                $messages[] = [
                    'type' => 'error',
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
