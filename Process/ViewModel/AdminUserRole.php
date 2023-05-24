<?php
namespace MiraklSeller\Process\ViewModel;

use Magento\Authorization\Model\Acl\AclRetriever;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class AdminUserRole implements ArgumentInterface
{
    const ACL_RESOURCE = 'MiraklSeller_Process::process';

    /**
     * @var AdminSession
     */
    private $adminSession;

    /**
     * @var AclRetriever
     */
    private $aclRetriever;

    /**
     * @param AdminSession $adminSession
     * @param AclRetriever $aclRetriever
     */
    public function __construct(
        AdminSession $adminSession,
        AclRetriever $aclRetriever
    ) {
        $this->adminSession = $adminSession;
        $this->aclRetriever = $aclRetriever;
    }

    /**
     * Check if current admin user is allowed to do process actions
     *
     * @return bool
     */
    public function isAllowed()
    {
        $adminUser = $this->adminSession->getUser();
        $roleId    = $adminUser->getRole()->getId();
        $resources = $this->aclRetriever->getAllowedResourcesByRole($roleId);

        return in_array(self::ACL_RESOURCE, $resources);
    }
}