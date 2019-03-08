<?php

declare(strict_types=1);

namespace Okvpn\Bundle\BetterOroBundle;

use Okvpn\Bundle\BetterOroBundle\DependencyInjection\CompilerPass\DataauditTokenPass;
use Okvpn\Bundle\BetterOroBundle\DependencyInjection\CompilerPass\DisableContainerResetExtensionPass;
use Okvpn\Bundle\BetterOroBundle\DependencyInjection\CompilerPass\MessageQueuePass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OkvpnBetterOroBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new MessageQueuePass());
        $container->addCompilerPass(new DataauditTokenPass());

        /**
         * Disable container_reset_extension, see
         * @link https://github.com/oroinc/platform/issues/754
         * @link https://github.com/oroinc/platform/issues/755
         * @link https://github.com/oroinc/platform/issues/764
         */
        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new DisableContainerResetExtensionPass());
            $container->moveCompilerPassBefore(
                DisableContainerResetExtensionPass::class,
                BuildExtensionsPass::class
            );
        }
    }
}
