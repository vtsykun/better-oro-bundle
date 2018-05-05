<?php

namespace Okvpn\Bundle\BetterOroBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NoResultException;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Security;

use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Exception\OrganizationAccessDeniedException;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class ContextListener
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Refresh organization context in token
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $tokenStorage = $this->getTokenStorage();
        $token = $tokenStorage->getToken();
        if ($token instanceof OrganizationContextTokenInterface && $token->getOrganizationContext()) {
            try {
                $token->setOrganizationContext(
                    $this->getOrganizationManager()->getOrganizationById($token->getOrganizationContext()->getId())
                );

                $organizationAccessDenied = false;
                $organizationId = $token->getOrganizationContext() ? $token->getOrganizationContext()->getId():null;
                $user = $token->getUser();
                if ($user instanceof AbstractUser) {
                    /** @var ArrayCollection $organizations */
                    $organizations = $user->getOrganizations(true);
                    $organizationAccessDenied = !(bool) $organizations->filter(
                        function (OrganizationInterface $organization) use ($organizationId) {
                            return $organizationId === $organization->getId();
                        }
                    );
                }

                if ($organizationAccessDenied) {
                    $exception = new OrganizationAccessDeniedException();
                    $exception->setOrganizationName($token->getOrganizationContext()->getName());
                    $exception->setToken($token);
                    $event->getRequest()->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
                    $tokenStorage->setToken(null);
                    throw $exception;
                }
            } catch (NoResultException $e) {
                $token->setAuthenticated(false);
            }
        }
    }

    /**
     * @return TokenStorageInterface
     */
    protected function getTokenStorage()
    {
        return $this->container->get('security.token_storage');
    }

    /**
     * @return OrganizationManager
     */
    protected function getOrganizationManager()
    {
        return $this->container->get('oro_organization.organization_manager');
    }
}
