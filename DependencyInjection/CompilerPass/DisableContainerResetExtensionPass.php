<?php

namespace Okvpn\Bundle\BetterOroBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DisableContainerResetExtensionPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $capabilities = $container->getParameter('okvpn.better_oro');

        if (true === $capabilities['mq_disable_container_reset']
            && $container->hasDefinition('oro_message_queue.consumption.container_reset_extension')
        ) {
            $def = $container->getDefinition('oro_message_queue.consumption.container_reset_extension');
            $def->clearTags();
        }
    }
}
