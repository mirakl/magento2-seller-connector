<?php
namespace MiraklSeller\Api\Helper\Client;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mirakl\Core\Client\AbstractApiClient;
use Mirakl\Core\Request\AbstractRequest;
use MiraklSeller\Api\Model\Client\Manager;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\Log\LoggerManager;
use MiraklSeller\Api\Model\Log\RequestLogValidator;

/**
 * @method string getLastRequestString()
 */
abstract class AbstractClient extends AbstractHelper
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var LoggerManager
     */
    protected $loggerManager;

    /**
     * @var RequestLogValidator
     */
    protected $requestLogValidator;

    /**
     * @param   Context $context
     * @param   Manager $manager
     * @param   LoggerManager          $loggerManager
     * @param   RequestLogValidator    $requestLogValidator
     */
    public function __construct(
        Context $context,
        Manager $manager,
        LoggerManager $loggerManager,
        RequestLogValidator $requestLogValidator
    ) {
        parent::__construct($context);

        $this->manager             = $manager;
        $this->loggerManager       = $loggerManager;
        $this->requestLogValidator = $requestLogValidator;
    }

    /**
     * Proxy to API client methods
     *
     * @param   string  $name
     * @param   array   $args
     * @return  mixed
     */
    public function __call($name, $args)
    {
        $connection = array_shift($args);
        if (!$connection instanceof Connection) {
            throw new \InvalidArgumentException('The first argument must be the connection.');
        }

        return call_user_func_array([$this->getClient($connection), $name], $args);
    }

    /**
     * @return  string
     */
    abstract protected function getArea();

    /**
     * @param   Connection  $connection
     * @return  AbstractApiClient
     */
    public function getClient(Connection $connection)
    {
        return $this->manager->get($connection, $this->getArea());
    }

    /**
     * @param   Connection      $connection
     * @param   AbstractRequest $request
     * @param   bool            $raw
     * @return  mixed
     */
    public function send(Connection $connection, AbstractRequest $request, $raw = false)
    {
        $client = $this->getClient($connection);
        $client->raw((bool) $raw);

        if ($this->requestLogValidator->validate($request)) {
            $logger = $this->loggerManager->getLogger();
            $messageFormatter = $this->loggerManager->getMessageFormatter();
            $client->setLogger($logger, $messageFormatter);
        }

        $this->_eventManager->dispatch('mirakl_seller_api_send_request_before', [
            'client'     => $client,
            'connection' => $connection,
            'request'    => $request,
            'helper'     => $this,
        ]);

        return $request->run($client);
    }
}