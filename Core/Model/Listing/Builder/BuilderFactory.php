<?php
namespace MiraklSeller\Core\Model\Listing\Builder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

class BuilderFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Construct
     *
     * @param   ObjectManagerInterface  $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param   string  $className
     * @return  BuilderInterface
     * @throws  LocalizedException
     */
    public function create($className)
    {
        $method = $this->objectManager->create($className);
        if (!$method instanceof BuilderInterface) {
            throw new LocalizedException(
                __('%1 class does not implement %2', $className, BuilderInterface::class)
            );
        }

        return $method;
    }
}
