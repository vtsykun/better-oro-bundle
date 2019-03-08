<?php

declare(strict_types=1);

namespace Okvpn\Bundle\BetterOroBundle\Audit;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuditTokenStorage implements TokenStorageInterface
{
    private $tokenStorage;
    private $organization;

    public function __construct(TokenStorageInterface $tokenStorage, int $organization)
    {
        $this->tokenStorage = $tokenStorage;
        $this->organization = $organization;
    }

    /**
     * {@inheritdoc}
     */
    public function getToken()
    {
        if ($token = $this->tokenStorage->getToken()) {
            if ($token instanceof OrganizationContextTokenInterface) {
                return $token;
            }
        }

        $entityReference = new Organization();
        $entityReference->setId($this->organization);

        $token = new OrganizationToken($entityReference);
        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function setToken(TokenInterface $token = null)
    {
        $this->tokenStorage->setToken($token);
    }
}
