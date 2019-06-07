<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Tracking;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use MiraklSeller\Core\Helper\Tracking as TrackingHelper;
use MiraklSeller\Process\Model\Process;

class Update extends AbstractTracking
{
    /**
     * @var TrackingHelper
     */
    protected $trackingHelper;

    /**
     * @param   Context         $context
     * @param   TrackingHelper  $trackingHelper
     */
    public function __construct(
        Context $context,
        TrackingHelper $trackingHelper
    ) {
        parent::__construct($context);
        $this->trackingHelper = $trackingHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $tracking = $this->getTracking(true);
        $messages = [];
        $refresh = false;

        try {
            $processes = $this->trackingHelper->updateTrackingsByType(
                [$tracking->getId()], $this->getTrackingType()
            );

            if (!count($processes)) {
                $messages[] = [
                    'type' => 'error',
                    'message' => __('This tracking cannot be updated.')
                ];
            } else {
                // Will contain only 1 process so run it synchronously
                foreach ($processes as $process) {
                    /** @var Process $process */
                    $process->run();

                    if ($process->isError()) {
                        $messages[] = [
                            'type' => 'error',
                            'message' => __('An error occurred while updating the tracking.')
                        ];
                    } else {
                        $messages[] = [
                            'type' => 'success',
                            'message' => __('The tracking has been updated successfully.')
                        ];
                        $refresh = true;
                    }
                }
            }
        } catch (\Exception $e) {
            $messages[] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
        }

        /** @var Json $resultLayout */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultLayout->setData(['messages' => $messages, 'refresh' => $refresh]);

        return $resultLayout;
    }
}
