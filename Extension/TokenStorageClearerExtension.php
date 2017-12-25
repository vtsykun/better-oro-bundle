<?php

namespace Okvpn\Bundle\BetterOroBundle\Extension;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Todo: The extension TokenStorageClearerExtension was removed since 2.3.6 and this job is handled by ContainerResetExtension.
 * but this bundle for performance disabled ContainerResetExtension
 * @see https://github.com/oroinc/platform/issues/754
 */
class TokenStorageClearerExtension extends AbstractExtension
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Context $context
     */
    public function onPostReceived(Context $context)
    {
        $this->tokenStorage->setToken(null);
    }
}
