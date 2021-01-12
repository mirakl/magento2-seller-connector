<?php
namespace MiraklSeller\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mirakl\MMP\Common\Domain\Collection\Message\Thread\ThreadParticipantCollection;
use Mirakl\MMP\Common\Domain\Message\Thread\Thread as ThreadModel;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadParticipant;
use Mirakl\MMP\Common\Domain\Reason\ReasonType;
use MiraklSeller\Api\Helper\Reason as ReasonApi;
use MiraklSeller\Api\Model\Connection;

class Thread extends AbstractHelper
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ReasonApi
     */
    protected $reasonApi;

    /**
     * @param   Context     $context
     * @param   Config      $config
     * @param   ReasonApi   $reasonApi
     */
    public function __construct(Context $context, Config $config, ReasonApi $reasonApi)
    {
        parent::__construct($context);
        $this->config = $config;
        $this->reasonApi = $reasonApi;
    }

    /**
     * @param   ThreadModel $thread
     * @param   array       $excludeTypes
     * @return  array
     */
    public function getThreadCurrentParticipantsNames(ThreadModel $thread, array $excludeTypes = [])
    {
        return $this->getThreadParticipantNames($thread->getCurrentParticipants(), $excludeTypes);
    }

    /**
     * @param   ThreadModel $thread
     * @param   array       $excludeTypes
     * @return  array
     */
    public function getThreadAuthorizedParticipantsNames(ThreadModel $thread, array $excludeTypes = [])
    {
        return $this->getThreadParticipantNames($thread->getAuthorizedParticipants(), $excludeTypes);
    }

    /**
     * @param   ThreadParticipantCollection $participants
     * @param   array                       $excludeTypes
     * @return  array
     */
    public function getThreadParticipantNames(ThreadParticipantCollection $participants, array $excludeTypes = [])
    {
        $participantsNames = [];

        /** @var ThreadParticipant $participant */
        foreach ($participants as $participant) {
            if (!empty($excludeTypes) && in_array($participant->getType(), $excludeTypes)) {
                continue;
            }
            $participantsNames[$participant->getType()] = $participant->getDisplayName();
        }

        return $participantsNames;
    }

    /**
     * @param   Connection  $connection
     * @param   ThreadModel $thread
     * @return  string
     */
    public function getThreadTopic(Connection $connection, ThreadModel $thread)
    {
        $thread = $thread->toArray();

        if (!isset($thread['topic']['type']) || !isset($thread['topic']['value'])) {
            return '';
        }

        $topicValue = $thread['topic']['value'];

        if ($thread['topic']['type'] == 'REASON_CODE') {
            /** @var \Mirakl\MMP\Shop\Domain\Reason $reason */
            $locale = $this->config->getLocale();
            foreach ($this->reasonApi->getTypeReasons($connection, ReasonType::ORDER_MESSAGING, $locale) as $reason) {
                if ($reason->getCode() == $topicValue) {
                    return $reason->getLabel();
                }
            }
        }

        return $topicValue;
    }
}
