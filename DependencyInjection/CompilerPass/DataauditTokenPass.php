<?php

declare(strict_types=1);

namespace Okvpn\Bundle\BetterOroBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DataauditTokenPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('okvpn.dataaudit.token_storage')) {
            return;
        }

        $container->getDefinition('oro_dataaudit.listener.send_changed_entities_to_message_queue')
            ->replaceArgument(1, new Reference('okvpn.dataaudit.token_storage'));
    }
}
