<?php

namespace Okvpn\Bundle\BetterOroBundle\DependencyInjection\CompilerPass;

use Okvpn\Bundle\BetterOroBundle\Logger\PreFilterHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MessageQueuePass implements CompilerPassInterface
{
    const CHANEL = 'okvpn_jobs';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $capabilities = $container->getParameter('okvpn.better_oro');

        if ($capabilities['mq_log_format'] && $container->hasDefinition('oro_message_queue.log.handler.console')) {
            $def = $container->getDefinition('oro_message_queue.log.handler.console');
            $def->setClass(PreFilterHandler::class);
        }

        $pidFileManagerId = 'oro_message_queue.consumption.dbal.pid_file_manager';
        if ($container->hasDefinition($pidFileManagerId)) {
            $extension = $container->getDefinition('okvpn.message_queue.extension.redeliver_orphan');
            $extension->replaceArgument(0, new Reference($pidFileManagerId));
        }

        $loggerId = sprintf('monolog.logger.%s', self::CHANEL);
        if ($capabilities['job_logs'] && $container->hasDefinition($loggerId)) {
            $container->setAlias('okvpn.jobs.logger', $loggerId);
            $definition = $container->getDefinition($loggerId);
            $definition->addMethodCall('pushHandler', [new Reference('okvpn.log.job_handler')]);
        }
    }
}
